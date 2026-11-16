<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftIntegrationTokenService;
use Illuminate\Http\Request;
use App\Models\Integration;
use Illuminate\Support\Facades\Auth;

class IntegrationsController extends Controller
{
    /**
     * Display integrations dashboard.
     * Connected = valid token (not expired, or auto-refreshed).
     */
    public function index()
    {
        $msConnected = !empty(session('microsoft_token')) || !empty(auth()->user()?->microsoft_token);
        $graphMailConnected = MicrosoftIntegrationTokenService::isConnected(MicrosoftIntegrationTokenService::KEY_GRAPH_MAIL);
        $phishingConnected = MicrosoftIntegrationTokenService::isConnected(MicrosoftIntegrationTokenService::KEY_PHISHING);
        $allowlistConnected = MicrosoftIntegrationTokenService::isConnected(MicrosoftIntegrationTokenService::KEY_ALLOWLIST);
        
        $integrations = [
            [
                'id' => 'ms_user_sync',
                'name' => 'Microsoft User Sync',
                'description' => 'Available in Azure AD (global service) and Azure AD for US Government. Automatically sync users from a company\'s Azure instance.',
                'icon' => 'fab fa-microsoft',
                'color' => 'primary',
                'status' => 'available',
                'is_connected' => false, // User sync is usually always available/connected handled differently
                'route' => 'sync.microsoft',
                'connect_label' => 'connect'
            ],
            [
                'id' => 'graph_mail_delivery',
                'name' => 'Graph API Mail Delivery',
                'description' => 'Use Microsoft Graph API to bypass Simple Mail Transfer Protocol (SMTP). Always use allowlisting. This API may be affected by the configurations in your tenant.',
                'icon' => 'fas fa-envelope',
                'color' => 'info',
                'status' => 'available',
                'is_connected' => $graphMailConnected,
                'route' => 'integrations.graph-mail.connect',
                'connect_label' => 'connect',
                'test_route' => 'integrations.graph-mail.test' // Added
            ],
            [
                'id' => 'report_phishing',
                'name' => 'Report Phishing Button',
                'description' => 'Install the phishing button add-in for Outlook. Pair this with phishing campaigns to get reported insights on user behavior and more.',
                'icon' => 'fas fa-shield-alt',
                'color' => 'warning',
                'status' => 'available',
                'is_connected' => $phishingConnected,
                'route' => 'integrations.phishing.connect',
                'connect_label' => 'connect',
                'test_route' => 'integrations.phishing.test' // Added
            ],
            [
                'id' => 'google_user_sync',
                'name' => 'Google User Sync',
                'description' => 'Automatically sync users from a company\'s Google Workspace instance.',
                'icon' => 'fab fa-google',
                'color' => 'danger',
                'status' => 'beta',
                'is_connected' => false,
                'route' => 'sync.google',
                'connect_label' => 'connect'
            ],
            [
                'id' => 'auto_allowlist',
                'name' => 'Microsoft Automated Allowlisting',
                'description' => 'Automatically allowlist IPs and domains to ensure our emails are not blocked by Microsoft\'s spam filters.',
                'icon' => 'fas fa-lock-open',
                'color' => 'success',
                'status' => 'available',
                'is_connected' => $allowlistConnected,
                'route' => 'integrations.allowlist.connect',
                'connect_label' => 'connect',
                'test_route' => 'integrations.allowlist.test' // Added
            ],
        ];
        
        return view('integrations.index', compact('integrations'));
    }
    /**
     * Disconnect an integration
     */
    public function disconnect($id)
    {
        $userId = auth()->id();
        
        switch ($id) {
            case 'graph_mail_delivery':
                MicrosoftIntegrationTokenService::forget(MicrosoftIntegrationTokenService::KEY_GRAPH_MAIL);
                break;
            case 'report_phishing':
                MicrosoftIntegrationTokenService::forget(MicrosoftIntegrationTokenService::KEY_PHISHING);
                break;
            case 'auto_allowlist':
                MicrosoftIntegrationTokenService::forget(MicrosoftIntegrationTokenService::KEY_ALLOWLIST);
                break;
        }
        
        return redirect()->route('integrations.index')
            ->with('success', 'Integration disconnected successfully.');
    }
}
