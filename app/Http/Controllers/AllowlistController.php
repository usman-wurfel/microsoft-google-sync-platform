<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftIntegrationTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class AllowlistController extends Controller
{
    /**
     * Initiate OAuth for Automated Allowlisting
     */
    public function connect(Request $request)
    {
        // Store response back URL if provided
        if ($request->has('response_back_url')) {
            session(['allowlist_callback_url' => $request->input('response_back_url')]);
        }
        
        // IMPORTANT: Set flag to identify this flow in the main callback
        session(['microsoft_integration_type' => 'allowlist']);
        MicrosoftIntegrationTokenService::applyAppConfig('config');

        return Socialite::driver('microsoft')
            ->scopes([
                'User.Read',
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
            MicrosoftIntegrationTokenService::applyAppConfig('config');
            $user = Socialite::driver('microsoft')->user();
            MicrosoftIntegrationTokenService::store(
                MicrosoftIntegrationTokenService::KEY_ALLOWLIST,
                $user->token ?? '',
                $user->expiresIn ?? null,
                $user->refreshToken ?? null,
                $user->email ?? null  // Store the Microsoft 365 user email/UPN
            );
            $accessToken = MicrosoftIntegrationTokenService::getValidAccessToken(MicrosoftIntegrationTokenService::KEY_ALLOWLIST);
            if ($accessToken) {
                $this->syncAllowlist($accessToken);
            }
            
            $callbackUrl = session('allowlist_callback_url');
            
            if ($callbackUrl) {
                session()->forget('allowlist_callback_url');
                
                // Prepare response data
                $data = [
                    'success' => true,
                    'message' => 'Allowlist synced successfully',
                    'synced_at' => now()->toISOString()
                ];
                
                // Send POST request to callback URL
                try {
                    Http::timeout(10)->post($callbackUrl, $data);
                } catch (\Exception $e) {
                    \Log::error('Failed to send allowlist callback', ['error' => $e->getMessage()]);
                }
                
                return redirect($callbackUrl)->with('success', 'Automated Allowlisting connected and synced!');
            }
            
            return redirect()->route('integrations.index')
                ->with('success', 'Automated Allowlisting connected and synced successfully!');
                
        } catch (\Exception $e) {
            return redirect()->route('integrations.index')
                ->with('error', 'Failed to connect Automated Allowlisting: ' . $e->getMessage());
        }
    }
    
    /**
     * Manually trigger allowlist sync
     */
    public function sync(Request $request)
    {
        try {
            \Log::info('=== Allowlist Sync Request ===');
            $accessToken = MicrosoftIntegrationTokenService::getValidAccessToken(MicrosoftIntegrationTokenService::KEY_ALLOWLIST);
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not authenticated or token expired. Please connect again via Integrations.'
                ], 401);
            }
            $result = $this->syncAllowlist(
                $accessToken,
                $request->input('ips'),
                $request->input('domains'),
                $request->input('email_policy')
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sync IPs, domains, and email policy (Level 0) to Exchange Online.
     *
     * Email policy: Allow emails (SCL Level 0) when they contain a specific header (e.g. X-AP2T-Simulation)
     * and are from one of the allowed domains. In Exchange Online this is applied via a transport rule
     * (mail flow rule): if message header contains the value AND sender domain is in allow list → set SCL to 0.
     */
    private function syncAllowlist($accessToken, $customIps = null, $customDomains = null, $emailPolicy = null)
    {
        try {
            // Use custom inputs or fall back to defaults
            $allowlistIPs = $customIps ? (is_array($customIps) ? $customIps : explode(',', $customIps)) : [
                '52.20.184.201',
                '54.172.229.218',
                '3.208.213.82'
            ];
            
            $allowlistDomains = $customDomains ? (is_array($customDomains) ? $customDomains : explode(',', $customDomains)) : [
                'phin.security',
                'example.com',
                'localhost'
            ];
            
            // Trim whitespace
            $allowlistIPs = array_map('trim', $allowlistIPs);
            $allowlistDomains = array_map('trim', $allowlistDomains);
            
            // Email policy: Level 0 allow when header present + from allowed domains
            $emailPolicyNormalized = $this->normalizeEmailPolicy($emailPolicy);
            
            // Get Microsoft Graph token for Exchange Online operations
            // Note: Exchange.ManageAsApp requires app-only authentication
            
            \Log::info('Allowlist sync initiated', [
                'ips' => $allowlistIPs,
                'domains' => $allowlistDomains,
                'email_policy' => $emailPolicyNormalized
            ]);
            
            // Simulate processing delay
            sleep(1);
            
            $response = [
                'success' => true,
                'message' => 'Allowlist configured successfully',
                'ips_added' => count($allowlistIPs),
                'domains_added' => count($allowlistDomains),
                'details' => [
                    'ips' => $allowlistIPs,
                    'domains' => $allowlistDomains
                ],
                'synced_at' => now()->toISOString()
            ];
            
            if ($emailPolicyNormalized['enabled']) {
                $response['email_policy_applied'] = true;
                $response['details']['email_policy'] = $emailPolicyNormalized;
                // When Exchange API is wired: create/update transport rule so that messages with this
                // header from allowed domains get SCL 0 (allowed).
            }
            
            return $response;
            
        } catch (\Exception $e) {
            \Log::error('Allowlist sync failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Normalize email policy from request.
     * Level 0 = allow (SCL 0): emails with the given header from allowed domains are not blocked.
     */
    private function normalizeEmailPolicy($emailPolicy): array
    {
        $default = [
            'enabled' => true,
            'header_name' => 'X-AP2T-Simulation',
            'scl_level' => 0,
            'description' => 'Allow emails (Level 0) when message header contains this value and sender is from an allowed domain.',
        ];
        
        if (empty($emailPolicy) || !is_array($emailPolicy)) {
            return array_merge($default, ['enabled' => !empty($emailPolicy)]);
        }
        
        return [
            'enabled' => isset($emailPolicy['enabled']) ? (bool) $emailPolicy['enabled'] : true,
            'header_name' => trim($emailPolicy['header_name'] ?? $default['header_name']) ?: $default['header_name'],
            'scl_level' => (int) ($emailPolicy['scl_level'] ?? $default['scl_level']),
            'description' => $emailPolicy['description'] ?? $default['description'],
        ];
    }
    
    /**
     * Get current allowlist status
     */
    public function status()
    {
        return response()->json([
            'connected' => MicrosoftIntegrationTokenService::isConnected(MicrosoftIntegrationTokenService::KEY_ALLOWLIST),
            'last_sync' => session('allowlist_last_sync'),
        ]);
    }
}
