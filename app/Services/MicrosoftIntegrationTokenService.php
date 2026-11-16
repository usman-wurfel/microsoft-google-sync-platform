<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * Store and refresh Microsoft OAuth tokens for integrations (Phishing Report, Graph Mail, Allowlist).
 * When token expires, auto-refresh if we have refresh_token; otherwise treat as disconnected.
 * Each integration uses its own app credentials (config, mail, phishing, user_sync) from .env.
 */
class MicrosoftIntegrationTokenService
{
    public const KEY_PHISHING = 'phishing_report_token';
    public const KEY_GRAPH_MAIL = 'graph_mail_token';
    public const KEY_ALLOWLIST = 'allowlist_token';

    /** Buffer seconds before expiry to consider token "expired" and refresh */
    private const EXPIRY_BUFFER = 60;

    /** Map integration type (session) to app key for credentials */
    public static function getAppKeyForIntegrationType(?string $type): string
    {
        return match ($type) {
            'phishing_report' => 'phishing',
            'graph_mail' => 'mail',
            'allowlist' => 'config',
            default => 'user_sync',
        };
    }

    /** Map token key to app key for refresh */
    public static function getAppKeyForTokenKey(string $key): string
    {
        return match ($key) {
            self::KEY_PHISHING => 'phishing',
            self::KEY_GRAPH_MAIL => 'mail',
            self::KEY_ALLOWLIST => 'config',
            default => 'user_sync',
        };
    }

    /** Set Laravel config so Socialite uses the given app's client_id/secret (for OAuth redirect/callback). */
    public static function applyAppConfig(string $appKey): void
    {
        $apps = config('services.microsoft.apps', []);
        $creds = $apps[$appKey] ?? [];
        $clientId = $creds['client_id'] ?? null;
        $clientSecret = $creds['client_secret'] ?? null;
        config([
            'services.microsoft.client_id' => $clientId,
            'services.microsoft.client_secret' => $clientSecret,
        ]);
    }

    /**
     * Store token with expiry and optional refresh token.
     *
     * @param string $key One of KEY_PHISHING, KEY_GRAPH_MAIL, KEY_ALLOWLIST
     * @param string $accessToken
     * @param int|null $expiresIn Seconds until expiry (from OAuth response)
     * @param string|null $refreshToken
     * @param string|null $userEmail The Microsoft 365 user email/UPN that authenticated
     */
    public static function store(string $key, string $accessToken, ?int $expiresIn = null, ?string $refreshToken = null, ?string $userEmail = null): void
    {
        $expiresAt = $expiresIn !== null && $expiresIn > 0
            ? time() + $expiresIn - self::EXPIRY_BUFFER
            : null;

        $payload = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
            'user_email' => $userEmail,
        ];

        Session::put($key, $payload);
        Session::save();

        if (auth()->check()) {
            Cache::put($key . '_' . auth()->id(), $payload, 60 * 60 * 24); // 24h cache for payload
        }
    }

    /**
     * Get the connected Microsoft user email (UPN).
     *
     * @param string $key One of KEY_PHISHING, KEY_GRAPH_MAIL, KEY_ALLOWLIST
     * @return string|null User email or null if not stored
     */
    public static function getConnectedUserEmail(string $key): ?string
    {
        $payload = self::getPayload($key);
        return $payload['user_email'] ?? null;
    }

    /**
     * Get a valid access token: return current if not expired; else refresh if possible; else clear and return null.
     *
     * @param string $key One of KEY_PHISHING, KEY_GRAPH_MAIL, KEY_ALLOWLIST
     * @return string|null Access token or null if disconnected/expired and cannot refresh
     */
    public static function getValidAccessToken(string $key): ?string
    {
        $payload = self::getPayload($key);
        if (!$payload || empty($payload['access_token'])) {
            return null;
        }

        $accessToken = $payload['access_token'];
        $expiresAt = $payload['expires_at'] ?? null;
        $refreshToken = $payload['refresh_token'] ?? null;

        // No expiry stored (legacy format) => treat as valid until we get 401 elsewhere
        if ($expiresAt === null) {
            return \is_string($accessToken) ? $accessToken : null;
        }

        // Not yet expired
        if ($expiresAt > time()) {
            return $accessToken;
        }

        // Expired: try refresh
        if ($refreshToken && \strlen($refreshToken) > 10) {
            $newPayload = self::refreshTokens($key, $refreshToken);
            if ($newPayload && !empty($newPayload['access_token'])) {
                return $newPayload['access_token'];
            }
        }

        // Expired and no refresh or refresh failed => disconnect
        self::forget($key);
        return null;
    }

    /**
     * Check if integration has a stored token (may still be expired; use getValidAccessToken for use).
     */
    public static function hasStoredToken(string $key): bool
    {
        $payload = self::getPayload($key);
        return $payload && !empty($payload['access_token']);
    }

    /**
     * Consider "connected" only if we have a valid (non-expired or refreshable) token.
     */
    public static function isConnected(string $key): bool
    {
        return self::getValidAccessToken($key) !== null;
    }

    /**
     * Clear stored token (disconnect).
     */
    public static function forget(string $key): void
    {
        Session::forget($key);
        Session::save();
        if (auth()->check()) {
            Cache::forget($key . '_' . auth()->id());
        }
    }

    private static function getPayload(string $key): ?array
    {
        $payload = Session::get($key);
        if ($payload && \is_array($payload)) {
            return $payload;
        }
        if (auth()->check()) {
            $payload = Cache::get($key . '_' . auth()->id());
            if ($payload && \is_array($payload)) {
                Session::put($key, $payload);
                return $payload;
            }
        }
        // Legacy: token stored as plain string
        $legacy = Session::get($key) ?? (auth()->check() ? Cache::get($key . '_' . auth()->id()) : null);
        if (\is_string($legacy) && $legacy !== '') {
            return ['access_token' => $legacy, 'refresh_token' => null, 'expires_at' => null];
        }
        return null;
    }

    private static function refreshTokens(string $key, string $refreshToken): ?array
    {
        $tenant = env('MICROSOFT_TENANT_ID', 'common');
        $appKey = self::getAppKeyForTokenKey($key);
        $creds = config('services.microsoft.apps.' . $appKey, []);
        $clientId = $creds['client_id'] ?? null;
        $clientSecret = $creds['client_secret'] ?? null;
        $redirectUri = config('services.microsoft.redirect');

        if (!$clientId || !$clientSecret) {
            \Log::warning('MicrosoftIntegrationTokenService: missing client_id or client_secret');
            return null;
        }

        $url = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
        $response = Http::asForm()->post($url, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        if (!$response->successful()) {
            \Log::warning('MicrosoftIntegrationTokenService: refresh failed', [
                'key' => $key,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $newRefresh = $data['refresh_token'] ?? $refreshToken;
        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;

        if (!$accessToken) {
            return null;
        }

        self::store($key, $accessToken, $expiresIn, $newRefresh);
        return self::getPayload($key);
    }
}
