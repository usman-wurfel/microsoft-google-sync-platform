<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MicrosoftGraphService
{
    /**
     * Get access token for Microsoft Graph using client credentials (app-only).
     * Use this for backend access to any mailbox when tenant has granted admin consent.
     *
     * @param string|null $tenantId Tenant ID (or null to use default from config)
     * @param string $appKey App key: 'phishing' (default), 'mail', 'config', 'user_sync'
     * @return string|null Access token or null on failure
     */
    public static function getClientCredentialsToken(?string $tenantId = null, string $appKey = 'phishing'): ?string
    {
        $tenantId = $tenantId ?? env('MICROSOFT_TENANT_ID');
        $creds = config('services.microsoft.apps.' . $appKey, []);
        $clientId = $creds['client_id'] ?? null;
        $clientSecret = $creds['client_secret'] ?? null;

        if (!$clientId || !$clientSecret || !$tenantId) {
            \Log::warning('Microsoft Graph: missing client_id, client_secret, or tenant_id');
            return null;
        }

        $url = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        $response = Http::asForm()->post($url, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            \Log::error('Microsoft Graph token failed', [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $data = $response->json();
        return $data['access_token'] ?? null;
    }
}
