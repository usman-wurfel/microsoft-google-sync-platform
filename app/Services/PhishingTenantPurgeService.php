<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tenant-wide purge: find a message by InternetMessageId across all user mailboxes
 * and move it to Junk (or delete). Uses Graph API with app-only token.
 */
class PhishingTenantPurgeService
{
    /**
     * Purge message from all mailboxes in the tenant (by InternetMessageId).
     * If reporter email + message_id are given, tries direct move in reporter's mailbox first (most reliable).
     *
     * @param string $tenantId
     * @param string|null $internetMessageId Typically like "<xxx@domain.com>" or without brackets
     * @param string|null $reporterEmail Reporter's email (to try direct move by message_id)
     * @param string|null $reporterMessageId Graph message ID in reporter's mailbox (saved when report was created)
     * @return array { purged_count, skipped_count, errors[] }
     */
    
    public static function purgeFromAllMailboxes(
        string $tenantId,
        ?string $internetMessageId,
        ?string $reporterEmail = null,
        ?string $reporterMessageId = null
    ): array {
        $result = ['purged_count' => 0, 'skipped_count' => 0, 'errors' => [], 'reporter_in_tenant' => false];

        if (empty($internetMessageId) || trim($internetMessageId) === '') {
            $result['errors'][] = 'internet_message_id is required';
            return $result;
        }

        $accessToken = MicrosoftGraphService::getClientCredentialsToken($tenantId);
        if (!$accessToken) {
            $result['errors'][] = 'Could not get access token for tenant';
            return $result;
        }

        $normalizedId = self::normalizeInternetMessageId($internetMessageId);
        Log::info('PhishingTenantPurge: starting', [
            'raw_internet_message_id' => $internetMessageId,
            'normalized_id' => $normalizedId,
            'tenant_id' => $tenantId,
            'reporter_email' => $reporterEmail,
            'reporter_message_id' => $reporterMessageId ? '(set)' : null,
        ]);

        $users = self::listTenantUsers($accessToken);
        $reporterUserId = self::resolveUserIdByEmail($accessToken, $reporterEmail);
        $reporterEmailNorm = $reporterEmail !== null && $reporterEmail !== '' ? strtolower(trim($reporterEmail)) : null;

        $result['reporter_in_tenant'] = $reporterUserId !== null;

        Log::info('PhishingTenantPurge: tenant users', [
            'count' => count($users),
            'upns' => array_map(fn ($u) => $u['userPrincipalName'] ?? $u['id'], $users),
            'mails' => array_map(fn ($u) => $u['mail'] ?? null, $users),
            'reporter_resolved_id' => $reporterUserId,
        ]);

        foreach ($users as $user) {
            $userId = $user['id'];
            $upn = $user['userPrincipalName'] ?? $userId;
            $mail = $user['mail'] ?? null;
            $isReporter = $reporterUserId !== null
                ? ($userId === $reporterUserId)
                : ($reporterEmailNorm && (
                    strtolower(trim((string) $upn)) === $reporterEmailNorm
                    || ($mail !== null && strtolower(trim($mail)) === $reporterEmailNorm)
                ));

            try {
                $found = 0;
                if ($isReporter && $reporterMessageId !== null && $reporterMessageId !== '') {
                    Log::info('PhishingTenantPurge: attempting direct move by message_id', ['user_id' => $userId, 'upn' => $upn]);
                    $found = self::moveMessageById($accessToken, $userId, $reporterMessageId);
                    if ($found > 0) {
                        Log::info('PhishingTenantPurge: moved by message_id in reporter mailbox', ['user_id' => $userId]);
                    }
                }
                if ($found === 0) {
                    $found = self::findAndMoveInMailbox($accessToken, $userId, $normalizedId);
                }
                if ($found > 0) {
                    $result['purged_count'] += $found;
                } else {
                    $result['skipped_count']++;
                }
            } catch (\Throwable $e) {
                $result['errors'][] = "{$upn}: " . $e->getMessage();
                $result['skipped_count']++;
            }
            usleep(300000); // 300ms between users to reduce rate limit risk
        }

        return $result;
    }

    /**
     * Move a message by its Graph message ID (folder-independent). Returns 1 if moved, 0 if not found/no access.
     */
    private static function moveMessageById(string $accessToken, string $userId, string $messageId): int
    {
        $userBase = "https://graph.microsoft.com/v1.0/users/" . urlencode($userId);
        $moveResponse = Http::withToken($accessToken)->post(
            "{$userBase}/messages/" . urlencode($messageId) . "/move",
            ['destinationId' => 'junkemail']
        );
        if ($moveResponse->successful()) {
            return 1;
        }
        $status = $moveResponse->status();
        $body = $moveResponse->json();
        $code = $body['error']['code'] ?? '';
        $message = $body['error']['message'] ?? '';
        Log::warning('PhishingTenantPurge: move by ID failed', [
            'user_id' => $userId,
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ]);
        return 0;
    }

    /**
     * Resolve reporter email to Graph user ID (so we match even if UPN format differs).
     */
    private static function resolveUserIdByEmail(string $accessToken, ?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }
        $esc = str_replace("'", "''", trim($email));
        $filter = "userPrincipalName eq '{$esc}' or mail eq '{$esc}'";
        $response = Http::withToken($accessToken)->get('https://graph.microsoft.com/v1.0/users', [
            '$filter' => $filter,
            '$select' => 'id',
            '$top' => 1,
        ]);
        if (!$response->successful()) {
            return null;
        }
        $data = $response->json();
        $value = $data['value'] ?? [];
        $first = $value[0] ?? null;
        return $first['id'] ?? null;
    }

    private static function listTenantUsers(string $accessToken): array
    {
        $all = [];
        $url = 'https://graph.microsoft.com/v1.0/users?$select=id,userPrincipalName,mail&$top=999';
        while ($url) {
            $response = Http::withToken($accessToken)->get($url);
            if (!$response->successful()) {
                Log::warning('PhishingTenantPurge: list users failed', ['status' => $response->status()]);
                return $all;
            }
            $data = $response->json();
            $all = array_merge($all, $data['value'] ?? []);
            $url = $data['@odata.nextLink'] ?? null;
        }
        return $all;
    }

    /**
     * Graph does NOT support $filter by internetMessageId on list messages. So we fetch recent messages
     * from Inbox + Junk, match by internetMessageId in PHP, then move to Junk.
     */
    private static function findAndMoveInMailbox(string $accessToken, string $userId, string $normalizedInternetMessageId): int
    {
        $userBase = "https://graph.microsoft.com/v1.0/users/" . urlencode($userId);
        $moved = 0;
        $folders = ['inbox', 'junkemail'];
        $top = 200;

        foreach ($folders as $folder) {
            $url = "{$userBase}/mailFolders/{$folder}/messages";
            $response = Http::withToken($accessToken)->get($url, [
                '$top' => $top,
                '$select' => 'id,internetMessageId',
            ]);

            if ($response->status() === 404 || $response->status() === 403) {
                continue;
            }
            $body = $response->json();
            if (isset($body['error'])) {
                $code = $body['error']['code'] ?? '';
                if ($code === 'MailboxNotEnabledForRESTAPI' || $code === 'ErrorInvalidUser') {
                    continue;
                }
                continue;
            }

            $messages = $body['value'] ?? [];
            $sampleId = null;
            foreach ($messages as $msg) {
                $imid = $msg['internetMessageId'] ?? '';
                if ($imid !== '' && $sampleId === null) {
                    $sampleId = $imid;
                }
                $compare = self::normalizeInternetMessageId($imid);
                if ($compare !== '' && $compare === $normalizedInternetMessageId) {
                    $msgId = $msg['id'];
                    $moveResponse = Http::withToken($accessToken)
                        ->post("{$userBase}/messages/{$msgId}/move", ['destinationId' => 'junkemail']);
                    if ($moveResponse->successful()) {
                        $moved++;
                        return $moved;
                    }
                }
            }
            Log::debug('PhishingTenantPurge: folder checked', [
                'user_id' => $userId,
                'folder' => $folder,
                'message_count' => count($messages),
                'sample_internet_message_id' => $sampleId,
            ]);
        }

        return $moved;
    }

    /**
     * Normalize Internet Message ID for comparison (Graph + header formats can differ).
     * Removes angle brackets, lowercase, trims, and strips whitespace/line breaks from header folding.
     */
    private static function normalizeInternetMessageId(?string $id): string
    {
        if ($id === null || $id === '') {
            return '';
        }
        $s = trim(trim((string) $id), '<>');
        $s = strtolower($s);
        $s = preg_replace('/\s+/', '', $s);
        return $s;
    }
}
