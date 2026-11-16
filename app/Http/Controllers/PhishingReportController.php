<?php

namespace App\Http\Controllers;

use App\Models\BlockedSender;
use App\Models\PhishingReport;
use App\Services\MicrosoftGraphService;
use App\Services\MicrosoftIntegrationTokenService;
use App\Services\PhishingTenantPurgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class PhishingReportController extends Controller
{
    /**
     * Admin Dashboard: List all reports
     */
    public function index()
    {
        $reports = PhishingReport::latest('received_at')->paginate(20);
        $blockedSenders = BlockedSender::latest('blocked_at')->take(50)->get();
        return view('integrations.phishing-dashboard', compact('reports', 'blockedSenders'));
    }

    /**
     * Tenant-wide purge: remove this report's message from all mailboxes in the tenant.
     */
    public function purge(PhishingReport $report)
    {
        if (empty($report->internet_message_id)) {
            return back()->with('error', 'This report has no Internet Message ID; cannot purge.');
        }
        $result = PhishingTenantPurgeService::purgeFromAllMailboxes(
            $report->tenant_id,
            $report->internet_message_id,
            $report->user_email,
            $report->message_id
        );
        $report->update(['action_taken' => 'PURGED', 'action_at' => now()]);

        $purgedCount = $result['purged_count'];
        $skippedCount = $result['skipped_count'] ?? 0;
        $errors = $result['errors'] ?? [];

        $msg = "Purged from {$purgedCount} mailbox(es).";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} mailbox(es) checked, message not found or no access.)";
        }
        if (!empty($errors)) {
            $msg .= ' Errors: ' . implode('; ', array_slice($errors, 0, 5));
        }
        if ($purgedCount === 0) {
            $reporterInTenant = $result['reporter_in_tenant'] ?? false;
            if (!$reporterInTenant && !empty($report->user_email)) {
                $msg .= ' Reporter (' . e($report->user_email) . ') is not in this tenant—report was likely from a personal Outlook/Gmail-linked account. Connect "Report Phishing" with a tenant account (e.g. user@YourTenant.onmicrosoft.com) and report again so purge can run.';
            } else {
                $msg .= ' Possible causes: app missing User.Read.All / Mail.ReadWrite, or message already removed from all mailboxes.';
            }
        }

        return back()->with($purgedCount > 0 ? 'success' : 'warning', $msg);
    }

    /**
     * Initiate OAuth for Phishing Report feature
     */
    public function connect(Request $request)
    {
        // Store response back URL if provided
        if ($request->has('response_back_url')) {
            session(['phishing_callback_url' => $request->input('response_back_url')]);
        }
        
        // IMPORTANT: Set flag to identify this flow in the main callback
        session(['microsoft_integration_type' => 'phishing_report']);
        MicrosoftIntegrationTokenService::applyAppConfig('phishing');

        return Socialite::driver('microsoft')
            ->scopes([
                'User.Read',
                'Mail.Read',
                'Mail.ReadWrite',
            ])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }
    
    /**
     * OAuth callback handler
     */
    public function callback(Request $request)
    {
        try {
            MicrosoftIntegrationTokenService::applyAppConfig('phishing');
            $user = Socialite::driver('microsoft')->user();
            MicrosoftIntegrationTokenService::store(
                MicrosoftIntegrationTokenService::KEY_PHISHING,
                $user->token ?? '',
                $user->expiresIn ?? null,
                $user->refreshToken ?? null,
                $user->email ?? null
            );
            $callbackUrl = session('phishing_callback_url');
            if ($callbackUrl) {
                session()->forget('phishing_callback_url');
                return redirect($callbackUrl)->with('success', 'Phishing Report feature connected successfully!');
            }
            return redirect()->route('integrations.index')
                ->with('success', 'Phishing Report feature connected successfully! Install the Outlook add-in to enable reporting.');
        } catch (\Exception $e) {
            return redirect()->route('integrations.index')
                ->with('error', 'Failed to connect Phishing Report: ' . $e->getMessage());
        }
    }
    
    /**
     * Report a phishing email
     */
    /**
     * Get recent messages for testing
     */
    public function getMessages(Request $request)
    {
        $accessToken = MicrosoftIntegrationTokenService::getValidAccessToken(MicrosoftIntegrationTokenService::KEY_PHISHING);
        if (!$accessToken || \strlen($accessToken) < 10) {
            return response()->json([
                'error' => 'Not authenticated or token expired. Please connect again via Integrations → Phishing Report.',
                'code' => 'InvalidOrMissingToken',
            ], 401);
        }

        $response = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me/messages?$top=5&$select=id,subject,from,receivedDateTime');

        if (!$response->successful()) {
            $body = $response->json();
            if ($response->status() === 401) {
                MicrosoftIntegrationTokenService::forget(MicrosoftIntegrationTokenService::KEY_PHISHING);
            }
            return response()->json([
                'error' => $body['error']['message'] ?? 'Graph API error',
                'code' => $body['error']['code'] ?? null,
            ], $response->status());
        }

        $data = $response->json();
        
        // Include the connected Microsoft email for app-only testing
        $connectedEmail = MicrosoftIntegrationTokenService::getConnectedUserEmail(MicrosoftIntegrationTokenService::KEY_PHISHING);
        $data['connectedEmail'] = $connectedEmail;
        
        return response()->json($data);
    }

    /**
     * Report a phishing email.
     *
     * Two modes:
     * 1) Add-in / app-only: send message_id, user (mailbox UPN), tenant_id. No session needed.
     * 2) Delegated: send message_id, reporter_email; user must be connected (session token).
     */
    public function report(Request $request)
    {
        try {
            \Log::info('=== Phishing Report Request ===', $request->only(['message_id', 'user', 'tenant_id', 'reporter_email']));

            $messageId = $request->input('message_id') ?? $request->input('messageId');
            $user = $request->input('user') ?? $request->input('mailbox');
            $tenantId = $request->input('tenant_id');
            $reporterEmail = $request->input('reporter_email');
            $reason = $request->input('reason');

            if (!$messageId) {
                return response()->json(['success' => false, 'error' => 'message_id is required'], 400);
            }

            $useAppOnly = !empty($tenantId) && !empty($user);

            if ($useAppOnly) {
                return $this->reportWithAppOnlyToken($messageId, $user, $tenantId, $reporterEmail ?? $user, $reason);
            }

            return $this->reportWithDelegatedToken($messageId, $reporterEmail, $reason);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * App-only flow: get token for tenant, fetch message, move to Junk.
     */
    private function reportWithAppOnlyToken(string $messageId, string $user, string $tenantId, string $reporterEmail, ?string $reason)
    {
        $accessToken = MicrosoftGraphService::getClientCredentialsToken($tenantId);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'error' => 'Could not get access token for tenant. Check admin consent and app registration.',
            ], 503);
        }

        // Step A: Fetch full message (for analysis / logging / future Defender submission)
        // Request headers and body for analysis
        $baseUrl = "https://graph.microsoft.com/v1.0/users/" . urlencode($user) . "/messages/" . urlencode($messageId);
        $queryUrl = $baseUrl . '?$select=id,subject,from,receivedDateTime,internetMessageHeaders,body,hasAttachments,webLink';
        
        $getResponse = Http::withToken($accessToken)->get($queryUrl);
        if (!$getResponse->successful()) {
            \Log::warning('Graph get message failed', ['user' => $user, 'message_id' => $messageId, 'status' => $getResponse->status()]);
            return response()->json([
                'success' => false,
                'error' => 'Could not fetch message from mailbox',
                'details' => $getResponse->json(),
            ], $getResponse->status());
        }

        $message = $getResponse->json();
        
        // --- ANALYSIS LOGIC START ---
        $analysis = $this->analyzeEmail($message);
        
        \Log::info('Phishing Analysis Result', [
            'message_id' => $messageId,
            'user' => $user,
            'subject' => $message['subject'] ?? 'No Subject',
            'risk_score' => $analysis['risk_score'],
            'verdict' => $analysis['verdict'],
            'flags' => $analysis['flags'],
            'urls_found' => count($analysis['urls']),
            'attachments' => $analysis['has_attachments']
        ]);
        // --- ANALYSIS LOGIC END ---
        
        // --- SAVE TO DATABASE ---
        try {
            $senderEmail = $message['from']['emailAddress']['address'] ?? '';
            $internetMessageId = $message['internetMessageId'] ?? null;
            
            // Extract internet message ID from headers if not in main body
            if (!$internetMessageId && !empty($message['internetMessageHeaders'])) {
                foreach ($message['internetMessageHeaders'] as $h) {
                    if (strtolower($h['name']) === 'message-id') {
                        $internetMessageId = $h['value'];
                        break;
                    }
                }
            }

            $report = PhishingReport::create([
                'tenant_id' => $tenantId,
                'user_email' => $user, // The reporter
                'message_id' => $messageId,
                'internet_message_id' => $internetMessageId,
                'sender' => $senderEmail,
                'subject' => $message['subject'] ?? null,
                'received_at' => isset($message['receivedDateTime']) ? \Carbon\Carbon::parse($message['receivedDateTime']) : now(),
                'risk_score' => $analysis['risk_score'],
                'verdict' => $analysis['verdict'],
                'analysis_details' => $analysis,
                'action_taken' => 'PENDING', // Will update after move
                'action_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save phishing report to DB: ' . $e->getMessage());
            // Don't fail the request if DB fails, just log it
        }
        // --- SAVE END ---

        // Step B: Move to Junk
        $moveResponse = Http::withToken($accessToken)
            ->post($baseUrl . '/move', ['destinationId' => 'junkemail']);

        if (!$moveResponse->successful()) {
            \Log::warning('Graph move to Junk failed', ['user' => $user, 'message_id' => $messageId]);
            return response()->json([
                'success' => false,
                'error' => 'Message fetched but failed to move to Junk',
                'details' => $moveResponse->json(),
            ], $moveResponse->status());
        }
        
        // Update DB status
        if (isset($report)) {
            $report->update(['action_taken' => 'MOVED_TO_JUNK']);
            $this->submitToDefender($accessToken, $messageId, $user, $senderEmail, $report);
            $this->addSenderToBlockListIfMalicious($report);
        }

        return response()->json([
            'success' => true,
            'action' => 'moved_to_junk',
            'message' => 'Email reported, analyzed, and moved to Junk folder',
            'analysis' => [
                'risk_score' => $analysis['risk_score'],
                'verdict' => $analysis['verdict'],
                'flags' => $analysis['flags']
            ],
            'reported_at' => now()->toISOString(),
            'reporter' => $reporterEmail,
        ]);
    }

    /**
     * Submit email to Microsoft Defender for analysis
     */
    private function submitToDefender(string $accessToken, string $messageId, string $recipientEmail, string $senderEmail, $reportModel = null)
    {
        try {
            $url = "https://graph.microsoft.com/v1.0/security/threatSubmission/emailThreats";
            
            $payload = [
                'emailRecipients' => [
                    ['emailAddress' => $recipientEmail]
                ],
                'payloadDelivery' => [
                    'messageId' => $messageId,
                    'isInternetMessageId' => false,
                ],
                'contentType' => 'email',
                'source' => 'user',
                'category' => 'phishing',
                'tenantId' => env('MICROSOFT_TENANT_ID'),
                'createdBy' => [
                    'emailAddress' => $recipientEmail
                ]
            ];
            
            $response = Http::withToken($accessToken)->post($url, $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                \Log::info('Submitted to Defender', ['id' => $data['id'] ?? 'unknown']);
                
                if ($reportModel) {
                    $reportModel->update([
                        'defender_submission_id' => $data['id'] ?? null,
                        'defender_status' => $data['status'] ?? 'submitted'
                    ]);
                }
                return true;
            } 
            
            \Log::warning('Defender submission failed', ['status' => $response->status(), 'body' => $response->json()]);
            return false;
            
        } catch (\Exception $e) {
            \Log::error('Defender submission exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * When verdict is MALICIOUS (or SUSPICIOUS), add sender to blocked list (DB).
     * Admin can then add these to Microsoft 365 tenant block list manually or via future API.
     */
    private function addSenderToBlockListIfMalicious(PhishingReport $report): void
    {
        $verdict = strtoupper($report->verdict ?? '');
        if ($verdict !== 'MALICIOUS' && $verdict !== 'SUSPICIOUS') {
            return;
        }
        $sender = trim($report->sender ?? '');
        if ($sender === '') {
            return;
        }
        $domain = BlockedSender::domainFromEmail($sender);
        try {
            BlockedSender::firstOrCreate(
                [
                    'tenant_id' => $report->tenant_id,
                    'sender_email' => $sender,
                ],
                [
                    'domain' => $domain ?: null,
                    'source_phishing_report_id' => $report->id,
                    'blocked_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            \Log::warning('BlockedSender create failed: ' . $e->getMessage());
        }
    }

    /**
     * Analyze email for potential threats
     */
    private function analyzeEmail($message)
    {
        $flags = [];
        $riskScore = 0;
        $urls = [];
        
        // 1. Check Authentication Headers (SPF/DKIM/DMARC)
        $headers = $message['internetMessageHeaders'] ?? [];
        $authHeader = null;
        foreach ($headers as $header) {
            if (strtolower($header['name']) === 'authentication-results') {
                $authHeader = $header['value'];
                break;
            }
        }
        
        if ($authHeader) {
            if (stripos($authHeader, 'spf=fail') !== false) {
                $flags[] = 'SPF_FAIL';
                $riskScore += 30;
            }
            if (stripos($authHeader, 'dkim=fail') !== false) {
                $flags[] = 'DKIM_FAIL';
                $riskScore += 30;
            }
            if (stripos($authHeader, 'dmarc=fail') !== false) {
                $flags[] = 'DMARC_FAIL';
                $riskScore += 40;
            }
        } else {
            $flags[] = 'MISSING_AUTH_HEADERS';
            $riskScore += 10;
        }
        
        // 2. Extract URLs from Body
        $bodyContent = $message['body']['content'] ?? '';
        if ($bodyContent) {
            preg_match_all('/href=["\'](https?:\/\/[^"\']+)["\']/i', $bodyContent, $matches);
            if (!empty($matches[1])) {
                $urls = array_unique($matches[1]);
                // Check for suspicious domains (basic check)
                foreach ($urls as $url) {
                    if (strpos($url, '.xyz') !== false || strpos($url, '.top') !== false || strpos($url, 'bit.ly') !== false) {
                        $flags[] = 'SUSPICIOUS_TLD_OR_SHORTENER';
                        $riskScore += 20;
                        break; // Only flag once
                    }
                }
                
                // High URL count
                if (count($urls) > 5) {
                    $flags[] = 'MANY_LINKS'; // 5+ links
                    $riskScore += 10;
                }
            }
        }
        
        // 3. Check Attachments
        if (!empty($message['hasAttachments'])) {
            $flags[] = 'HAS_ATTACHMENTS';
            $riskScore += 10; // Slight risk increase, would need to check file types for more
        }
        
        // 4. Sender Domain Check (External vs Internal - Simplified)
        $senderEmail = $message['from']['emailAddress']['address'] ?? '';
        $userDomain = substr(strrchr($message['toRecipients'][0]['emailAddress']['address'] ?? '', "@"), 1);
        $senderDomain = substr(strrchr($senderEmail, "@"), 1);
        
        if ($senderDomain !== $userDomain) {
            $flags[] = 'EXTERNAL_SENDER'; 
            // Standard for external, but risky if combined with other flags
        }

        // Determine Verdict
        $verdict = 'CLEAN';
        if ($riskScore >= 70) {
            $verdict = 'MALICIOUS';
        } elseif ($riskScore >= 30) {
            $verdict = 'SUSPICIOUS';
        }
        
        return [
            'risk_score' => $riskScore,
            'verdict' => $verdict,
            'flags' => $flags,
            'urls' => $urls,
            'has_attachments' => !empty($message['hasAttachments']),
            'auth_results' => $authHeader
        ];
    }

    /**
     * Delegated flow: use session token (current user's mailbox).
     * Fetches message, analyzes, saves to DB, moves to Junk, then block list so report shows on dashboard.
     */
    private function reportWithDelegatedToken(string $messageId, ?string $reporterEmail, ?string $reason)
    {
        $accessToken = MicrosoftIntegrationTokenService::getValidAccessToken(MicrosoftIntegrationTokenService::KEY_PHISHING);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'error' => 'Not authenticated. Please connect Phishing integration first (Integrations → Report Phishing → Connect).',
            ], 401);
        }

        $reporterEmail = $reporterEmail ?? auth()->user()?->email ?? 'unknown';

        // Resolve actual reporter identity from Graph (so purge can find this user in tenant)
        $meResponse = Http::withToken($accessToken)->get('https://graph.microsoft.com/v1.0/me?$select=userPrincipalName,mail');
        $reporterIdentity = $reporterEmail;
        if ($meResponse->successful()) {
            $me = $meResponse->json();
            $reporterIdentity = $me['mail'] ?? $me['userPrincipalName'] ?? $reporterEmail;
        }

        // 1) Fetch full message so we can analyze and save to DB
        $getUrl = "https://graph.microsoft.com/v1.0/me/messages/" . urlencode($messageId) . "?\$select=id,subject,from,receivedDateTime,internetMessageHeaders,body,hasAttachments,internetMessageId,toRecipients";
        $getResponse = Http::withToken($accessToken)->get($getUrl);
        if (!$getResponse->successful()) {
            \Log::warning('Delegated: Graph get message failed', ['message_id' => $messageId, 'status' => $getResponse->status()]);
            return response()->json([
                'success' => false,
                'error' => 'Could not fetch message from mailbox',
                'details' => $getResponse->json(),
            ], $getResponse->status());
        }

        $message = $getResponse->json();
        $analysis = $this->analyzeEmail($message);
        $senderEmail = $message['from']['emailAddress']['address'] ?? '';
        $internetMessageId = $message['internetMessageId'] ?? null;
        if (!$internetMessageId && !empty($message['internetMessageHeaders'])) {
            foreach ($message['internetMessageHeaders'] as $h) {
                if (strtolower($h['name']) === 'message-id') {
                    $internetMessageId = $h['value'];
                    break;
                }
            }
        }

        $tenantId = env('MICROSOFT_TENANT_ID', 'delegated');

        // 2) Save to DB so report shows on dashboard (user_email = Graph identity so purge can resolve)
        try {
            $report = PhishingReport::create([
                'tenant_id' => $tenantId,
                'user_email' => $reporterIdentity,
                'message_id' => $messageId,
                'internet_message_id' => $internetMessageId,
                'sender' => $senderEmail,
                'subject' => $message['subject'] ?? null,
                'received_at' => isset($message['receivedDateTime']) ? \Carbon\Carbon::parse($message['receivedDateTime']) : now(),
                'risk_score' => $analysis['risk_score'],
                'verdict' => $analysis['verdict'],
                'analysis_details' => $analysis,
                'action_taken' => 'PENDING',
                'action_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Delegated: Failed to save report to DB: ' . $e->getMessage());
        }

        // 3) Move to Junk
        $response = Http::withToken($accessToken)
            ->post("https://graph.microsoft.com/v1.0/me/messages/{$messageId}/move", [
                'destinationId' => 'junkemail',
            ]);

        \Log::info('Graph API Response (delegated):', ['status' => $response->status(), 'body' => $response->json()]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to move email to Junk',
                'details' => $response->json(),
            ], $response->status());
        }

        if (isset($report)) {
            $report->update(['action_taken' => 'MOVED_TO_JUNK']);
            $this->addSenderToBlockListIfMalicious($report);
        }

        return response()->json([
            'success' => true,
            'action' => 'moved_to_junk',
            'message' => 'Email reported, analyzed, and moved to Junk. It will appear on the Phishing Reports dashboard.',
            'reported_at' => now()->toISOString(),
            'analysis' => [
                'risk_score' => $analysis['risk_score'],
                'verdict' => $analysis['verdict'],
            ],
        ]);
    }
    
    /**
     * Generate Outlook Add-in Manifest
     */
    public function generateManifest()
    {
        $manifest = view('integrations.phishing-manifest')->render();
        
        return response($manifest)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="phishing-report-addin.xml"');
    }
}
