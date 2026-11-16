<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftIntegrationTokenService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\SyncedUser;
use App\Models\SyncedGroup;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Http;

class MicrosoftSyncController extends Controller
{
    public function redirect(Request $request)
    {
        // Store response back URL in session if provided
        if ($request->has('response_back_url')) {
            session(['sync_callback_url' => $request->input('response_back_url')]);
        }
        MicrosoftIntegrationTokenService::applyAppConfig('user_sync');

        return Socialite::driver('microsoft')
            ->scopes([
                'User.Read',
                'User.Read.All',
                'Group.Read.All',
                'Directory.Read.All',
                'Team.ReadBasic.All',
                'TeamMember.Read.All',
                'Organization.Read.All',
            ])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Proxy endpoint for external clients
     * Accepts callback URL and triggers sync process
     */
    public function proxy(Request $request)
    {
        // Validate response back URL
        $responseBackUrl = $request->input('response_back_url');
        if (!$responseBackUrl) {
            return response()->json([
                'success' => false,
                'error' => 'response_back_url parameter is required'
            ], 400);
        }
        
        // Validate URL format
        if (!filter_var($responseBackUrl, FILTER_VALIDATE_URL)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid response back URL format'
            ], 400);
        }
        
        // Store response back URL and user ID in session
        session(['sync_callback_url' => $responseBackUrl]);
        
        // Store user ID if authenticated (for use after OAuth callback)
        if (auth()->check()) {
            session(['sync_user_id' => auth()->id()]);
        }
        MicrosoftIntegrationTokenService::applyAppConfig('user_sync');

        // Redirect to Microsoft OAuth (User Sync only – no mail scopes)
        return Socialite::driver('microsoft')
            ->scopes([
                'User.Read',
                'User.Read.All',
                'Group.Read.All',
                'Directory.Read.All',
                'Team.ReadBasic.All',
                'TeamMember.Read.All',
                'Organization.Read.All',
            ])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Callback handler for receiving data from sync
     * This endpoint receives POST data from the sync process
     */
    public function callbackHandler(Request $request, $domain = null)
    {
        // Get data from POST request
        $data = $request->input('data');
        
        // If data is JSON string, decode it
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        
        // Return JSON response with received data
        return response()->json([
            'success' => true,
            'message' => 'Data received successfully',
            'domain' => $domain,
            'timestamp' => now()->toISOString(),
            'data' => $data
        ]);
    }
    
    
    /**
     * Prepare synced data for response
     */
    private function prepareSyncData($syncLog = null, $error = null)
    {
        // Check if user is authenticated
        $userId = auth()->id();
        if (!$userId) {
            return null;
        }
        
        // Get synced data
        $users = SyncedUser::where('user_id', $userId)
            ->where('platform', 'microsoft')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'external_id' => $user->external_id,
                    'email' => $user->email,
                    'display_name' => $user->display_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'job_title' => $user->job_title,
                    'department' => $user->department,
                    'phone_number' => $user->phone_number,
                    'mobile_phone' => $user->mobile_phone,
                    'office_location' => $user->office_location,
                    'account_enabled' => $user->account_enabled,
                    'group_id' => $user->group_id,
                    'group_name' => $user->group_name,
                    'raw_data' => $user->raw_data,
                ];
            });
        
        $groups = SyncedGroup::where('user_id', $userId)
            ->where('platform', 'microsoft')
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'external_id' => $group->external_id,
                    'display_name' => $group->display_name,
                    'description' => $group->description,
                    'email' => $group->email,
                    'group_type' => $group->group_type,
                    'security_enabled' => $group->security_enabled,
                    'mail_enabled' => $group->mail_enabled,
                    'members' => $group->members,
                    'owners' => $group->owners,
                    'raw_data' => $group->raw_data,
                ];
            });
        
        return [
            'success' => $error === null,
            'error' => $error,
            'sync_log' => $syncLog ? [
                'id' => $syncLog->id,
                'status' => $syncLog->status,
                'users_synced' => $syncLog->users_synced,
                'groups_synced' => $syncLog->groups_synced,
                'started_at' => $syncLog->started_at,
                'completed_at' => $syncLog->completed_at,
            ] : null,
            'users' => $users,
            'groups' => $groups,
            'total_users' => $users->count(),
            'total_groups' => $groups->count(),
            'synced_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Redirect to callback URL with POST data using auto-submit form
     */
    private function redirectWithPostData($callbackUrl, $data)
    {
        // Encode data as JSON
        $jsonData = json_encode($data);
        
        // Return view with auto-submitting POST form
        return response()->view('callback-redirect', [
            'callback_url' => $callbackUrl,
            'data' => $jsonData
        ]);
    }
    
    /**
     * Send synced data to callback URL (in background, non-blocking)
     */
    private function sendDataToCallback($callbackUrl, $syncLog = null, $error = null)
    {
        try {
            // Prepare data
            $data = $this->prepareSyncData($syncLog, $error);
            
            if (!$data) {
                \Log::error('Failed to prepare sync data for callback');
                return;
            }
            
            // Try to send data to callback URL (non-blocking)
            // Use GET or POST based on what works
            try {
                // First try POST
                $response = Http::timeout(10)->post($callbackUrl, $data);
                
                if (!$response->successful()) {
                    // If POST fails, try GET
                    $response = Http::timeout(10)->get($callbackUrl, $data);
                }
                
                \Log::info('Callback URL called successfully', [
                    'callback_url' => $callbackUrl,
                    'status' => $response->status()
                ]);
                
            } catch (\Exception $e) {
                // Log error but don't fail the main response
                \Log::error('Failed to send data to callback URL', [
                    'callback_url' => $callbackUrl,
                    'error' => $e->getMessage()
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in sendDataToCallback', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function callback(Request $request)
    {
        try {
            $integrationType = session('microsoft_integration_type');
            $appKey = MicrosoftIntegrationTokenService::getAppKeyForIntegrationType($integrationType);
            MicrosoftIntegrationTokenService::applyAppConfig($appKey);

            $user = Socialite::driver('microsoft')->user();
            $accessToken = $user->token;

            // === PERSONAL ACCOUNT CHECK ===
            // Block personal Microsoft accounts (outlook.com, hotmail.com, live.com, etc.)
            // Only allow organizational/tenant (work/school) accounts
            $isPersonalAccount = $this->isPersonalMicrosoftAccount($user, $accessToken);
            
            if ($isPersonalAccount) {
                \Log::warning('Personal Microsoft account connection blocked', [
                    'email' => $user->email ?? 'unknown',
                    'name' => $user->name ?? 'unknown',
                ]);
                
                // Clear any integration session flags
                $integrationType = session('microsoft_integration_type');
                session()->forget('microsoft_integration_type');
                session()->forget('graph_mail_callback_url');
                session()->forget('phishing_callback_url');
                session()->forget('sync_callback_url');
                session()->forget('sync_user_id');
                
                // Determine redirect based on context
                if ($integrationType) {
                    return redirect()->route('integrations.index')
                        ->with('error', 'Personal Microsoft accounts (Outlook, Hotmail, Live) are not supported. Please connect using a Microsoft Work/School (Tenant) account.');
                }
                
                // For sync flow, check for callback URL
                $callbackUrl = session('sync_callback_url');
                if ($callbackUrl) {
                    session()->forget('sync_callback_url');
                    return $this->redirectWithPostData($callbackUrl, [
                        'success' => false,
                        'error' => 'Personal Microsoft accounts are not supported. Please use a Microsoft Work/School (Tenant) account.'
                    ]);
                }
                
                return redirect()->route('sync.dashboard')
                    ->with('error', 'Personal Microsoft accounts (Outlook, Hotmail, Live) are not supported. Please connect using a Microsoft Work/School (Tenant) account.');
            }
            // === END PERSONAL ACCOUNT CHECK ===
            
            // === HANDLING INTEGRATIONS FLOW ===
            // Since all callbacks come here (Production Redirect URI limitation), 
            // we check if this is an integration request.
            if (session()->has('microsoft_integration_type')) {
                $type = session('microsoft_integration_type');
                session()->forget('microsoft_integration_type'); // Clear flag
                
                \Log::info("Handling Microsoft Integration Callback: $type");
                
                if ($type === 'graph_mail') {
                    $tokenString = \is_array($accessToken) && !empty($accessToken['access_token'])
                        ? $accessToken['access_token']
                        : (\is_string($accessToken) ? $accessToken : null);
                    MicrosoftIntegrationTokenService::store(
                        MicrosoftIntegrationTokenService::KEY_GRAPH_MAIL,
                        $tokenString ?? '',
                        $user->expiresIn ?? null,
                        $user->refreshToken ?? null,
                        $user->email ?? null
                    );
                    
                    // Handle stored callback URL
                    if (session()->has('graph_mail_callback_url')) {
                        $url = session('graph_mail_callback_url');
                        session()->forget('graph_mail_callback_url');
                        return redirect($url);
                    }
                    
                    // Redirect to test page
                    return redirect()->route('integrations.graph-mail.test')
                        ->with('success', 'Graph Mail connected!');
                }
                
                if ($type === 'phishing_report') {
                    $tokenString = \is_array($accessToken) && !empty($accessToken['access_token'])
                        ? $accessToken['access_token']
                        : (\is_string($accessToken) ? $accessToken : null);
                    MicrosoftIntegrationTokenService::store(
                        MicrosoftIntegrationTokenService::KEY_PHISHING,
                        $tokenString ?? '',
                        $user->expiresIn ?? null,
                        $user->refreshToken ?? null,
                        $user->email ?? null
                    );
                    if (session()->has('phishing_callback_url')) {
                        $url = session('phishing_callback_url');
                        session()->forget('phishing_callback_url');
                        return redirect($url)->with('success', 'Phishing Report connected!');
                    }
                    return redirect()->route('integrations.index')
                        ->with('success', 'Phishing Report connected!');
                }

                if ($type === 'allowlist') {
                    $tokenString = \is_array($accessToken) && !empty($accessToken['access_token'])
                        ? $accessToken['access_token']
                        : (\is_string($accessToken) ? $accessToken : null);
                    MicrosoftIntegrationTokenService::store(
                        MicrosoftIntegrationTokenService::KEY_ALLOWLIST,
                        $tokenString ?? '',
                        $user->expiresIn ?? null,
                        $user->refreshToken ?? null,
                        $user->email ?? null
                    );
                    // Trigger sync logic (Simplified)
                    return redirect()->route('integrations.index')
                        ->with('success', 'Allowlist connected!');
                }
            }
            // === END INTEGRATION HANDLING ===
            
            // Get user ID from session or auth
            $userId = auth()->id();
            if (!$userId) {
                // Try to get user ID from session (stored before OAuth redirect)
                $userId = session('sync_user_id');
            }
            
            // Check if callback URL exists in session
            $callbackUrl = session('sync_callback_url');
            
            // Check if this is a work/school account (Azure AD) or personal account (MSA)
            $accountType = $this->detectAccountType($accessToken);
            
            if ($accountType === 'personal') {
                // For personal accounts, we can only sync the current user's basic info
                if ($userId) {
                    $this->syncPersonalAccount($accessToken, $user);
                }
                
                if ($callbackUrl) {
                    session()->forget('sync_callback_url');
                    session()->forget('sync_user_id');
                    
                    // Prepare data
                    $data = $this->prepareSyncData(null, 'Personal account - limited data available');
                    
                    if ($data) {
                        // Also try to send POST request in background
                        $this->sendDataToCallback($callbackUrl, null, 'Personal account - limited data available');
                        
                        // Redirect browser to callback URL with POST form
                        return $this->redirectWithPostData($callbackUrl, $data);
                    }
                    
                    // If data preparation failed, redirect with error
                    return $this->redirectWithPostData($callbackUrl, [
                        'success' => false,
                        'error' => 'User not authenticated'
                    ]);
                }
                
                return redirect()->route('sync.dashboard')
                    ->with('warning', 'Microsoft Personal Account detected. Only basic user information was synced. For full directory sync, please use a Microsoft Work/School Account (Azure AD).');
            }
            
            if (!$userId) {
                // If no user ID, redirect to login or return error
                if ($callbackUrl) {
                    session()->forget('sync_callback_url');
                    return $this->redirectWithPostData($callbackUrl, [
                        'success' => false,
                        'error' => 'User not authenticated. Please log in first.'
                    ]);
                }
                return redirect()->route('login')->with('error', 'Please log in to sync Microsoft data.');
            }
            
            // Start sync log
            $syncLog = SyncLog::create([
                'user_id' => $userId,
                'platform' => 'microsoft',
                'sync_type' => 'full',
                'status' => 'started',
                'started_at' => now(),
            ]);

            // Sync users, groups, and directories
            // Skip general users sync, only sync group members
            // $this->syncUsers($accessToken, $syncLog);
            $this->syncGroups($accessToken, $syncLog);
            // $this->syncDirectories($accessToken, $syncLog); // Temporarily disabled

            // Update sync log
            $syncLog->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Check if callback URL exists in session
            if ($callbackUrl) {
                session()->forget('sync_callback_url');
                session()->forget('sync_user_id');
                
                // Prepare data
                $data = $this->prepareSyncData($syncLog, null);
                
                if ($data) {
                    // Also try to send POST request in background (non-blocking)
                    $this->sendDataToCallback($callbackUrl, $syncLog, null);
                    
                    // Redirect browser to callback URL with POST form
                    return $this->redirectWithPostData($callbackUrl, $data);
                }
                
                // If data preparation failed, redirect with error
                return $this->redirectWithPostData($callbackUrl, [
                    'success' => false,
                    'error' => 'Failed to prepare sync data'
                ]);
            }

            return redirect()->route('sync.dashboard')
                ->with('success', 'Microsoft users and groups synced successfully!');

        } catch (\Exception $e) 
        {
            
            if (isset($syncLog)) {
                $syncLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            // Check if callback URL exists in session
            $callbackUrl = session('sync_callback_url');
            if ($callbackUrl) {
                session()->forget('sync_callback_url');
                session()->forget('sync_user_id');
                
                // Prepare error data
                $data = $this->prepareSyncData(null, $e->getMessage());
                
                if ($data) {
                    // Also try to send POST request in background
                    $this->sendDataToCallback($callbackUrl, null, $e->getMessage());
                    
                    // Redirect browser to callback URL with POST form
                    return $this->redirectWithPostData($callbackUrl, $data);
                }
                
                // If data preparation failed, redirect with error message
                return $this->redirectWithPostData($callbackUrl, [
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            // Check for specific MSA error
            if (strpos($e->getMessage(), 'MSA accounts') !== false) {
                return redirect()->route('sync.dashboard')
                    ->with('error', 'Microsoft Personal Account detected. Please use a Microsoft Work/School Account (Azure AD) for syncing users and groups.');
            }

            return redirect()->route('sync.dashboard')
                ->with('error', 'Failed to sync Microsoft data: ' . $e->getMessage());
        }
    }

    private function syncUsers($accessToken, $syncLog)
    {
        try {
            
            // First, let's check total users count
            try {
                $countResponse = Http::withToken($accessToken)
                    ->get('https://graph.microsoft.com/v1.0/users/$count', [
                        'ConsistencyLevel' => 'eventual'
                    ]);
                
                if ($countResponse->successful()) {
                    $totalUsers = $countResponse->body();
                } else {
                }
            } catch (\Exception $e) {
            }
            
            // Get all users with pagination (including disabled users)
            $allUsers = [];
            $nextLink = 'https://graph.microsoft.com/v1.0/users';
            
            while ($nextLink) {
                $response = Http::withToken($accessToken)
                    ->get($nextLink, [
                        '$select' => 'id,displayName,mail,userPrincipalName,givenName,surname,jobTitle,department,officeLocation,businessPhones,mobilePhone,accountEnabled,preferredLanguage,usageLocation,userType,createdDateTime,creationType,externalUserState,identities,assignedLicenses,assignedPlans,provisionedPlans,streetAddress,city,state,postalCode,country,companyName,employeeId,employeeType,employeeHireDate',
                        '$top' => 999 // Get maximum users per page
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $currentPageUsers = $data['value'] ?? [];
                    $allUsers = array_merge($allUsers, $currentPageUsers);
                    
                    // Check if there's a next page - Microsoft Graph uses @odata.nextLink
                    $nextLink = $data['@odata.nextLink'] ?? null;
                    
                    // If no nextLink, we're done
                    if (!$nextLink) {
                        break;
                    }
                } else {
                    break;
                }
            }

            if (!empty($allUsers)) {
            $users = $allUsers;
            $syncedCount = 0;

            foreach ($users as $userData) {
                // Temporarily disable these API calls to avoid the "id in" filter issue
                $photoUrl = null;
                $teamsPhone = null;
                $managerEmail = null;

                $user = SyncedUser::updateOrCreate(
                    ['external_id' => $userData['id'], 'user_id' => auth()->id()],
                    [
                        'user_id' => auth()->id(),
                        'platform' => 'microsoft',
                        'email' => $userData['mail'] ?? $userData['userPrincipalName'],
                        'display_name' => $userData['displayName'],
                        'first_name' => $userData['givenName'] ?? null,
                        'last_name' => $userData['surname'] ?? null,
                        'job_title' => $userData['jobTitle'] ?? null,
                        'department' => $userData['department'] ?? null,
                        'phone_number' => $userData['businessPhones'][0] ?? null,
                        'mobile_phone' => $userData['mobilePhone'] ?? null,
                        'office_location' => $userData['officeLocation'] ?? null,
                        'manager_email' => $managerEmail,
                        'account_enabled' => $userData['accountEnabled'] ?? true,
                        // Microsoft Graph specific fields
                        'user_principal_name' => $userData['userPrincipalName'] ?? null,
                        'preferred_language' => $userData['preferredLanguage'] ?? null,
                        'usage_location' => $userData['usageLocation'] ?? null,
                        'user_type' => $userData['userType'] ?? null,
                        'created_date_time' => $userData['createdDateTime'] ?? null,
                        'creation_type' => $userData['creationType'] ?? null,
                        'external_user_state' => $userData['externalUserState'] ?? null,
                        'identities' => $userData['identities'] ?? null,
                        'assigned_licenses' => $userData['assignedLicenses'] ?? null,
                        'assigned_plans' => $userData['assignedPlans'] ?? null,
                        'provisioned_plans' => $userData['provisionedPlans'] ?? null,
                        
                        // Address fields
                        'street_address' => $userData['streetAddress'] ?? null,
                        'city' => $userData['city'] ?? null,
                        'state' => $userData['state'] ?? null,
                        'postal_code' => $userData['postalCode'] ?? null,
                        'country' => $userData['country'] ?? null,
                        
                        // Company fields
                        'company_name' => $userData['companyName'] ?? null,
                        'employee_id' => $userData['employeeId'] ?? null,
                        'employee_type' => $userData['employeeType'] ?? null,
                        'employee_hire_date' => $userData['employeeHireDate'] ?? null,
                        
                        // Profile photo and Teams phone
                        'profile_photo_url' => $photoUrl,
                        'teams_phone' => $teamsPhone,
                        
                        'raw_data' => array_merge($userData, [
                            'profile_photo_url' => $photoUrl,
                            'teams_phone' => $teamsPhone,
                            'manager_email' => $managerEmail
                        ]),
                    ]
                );
                $syncedCount++;
            }

            $syncLog->update(['users_synced' => $syncedCount]);
            } else {
                throw new \Exception('Microsoft users sync failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function syncGroups($accessToken, $syncLog)
    {
        try {
            
            // Get all groups with pagination
            $allGroups = [];
            $nextLink = 'https://graph.microsoft.com/v1.0/groups';
            
            while ($nextLink) {
                $response = Http::withToken($accessToken)
                    ->get($nextLink, [
                        '$select' => 'id,displayName,description,mail,groupTypes,securityEnabled,mailEnabled',
                        '$top' => 999 // Get maximum groups per page
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $currentPageGroups = $data['value'] ?? [];
                    $allGroups = array_merge($allGroups, $currentPageGroups);
                    
                    // Check if there's a next page - Microsoft Graph uses @odata.nextLink
                    $nextLink = $data['@odata.nextLink'] ?? null;
                    
                    // If no nextLink, we're done
                    if (!$nextLink) {
                        break;
                    }
                } else {
                    break;
                }
            }

            if (!empty($allGroups)) {
            $groups = $allGroups;
            $syncedCount = 0;

            foreach ($groups as $groupData) {
                // Get ALL group members and sync them individually
                $membersCount = 0;
                $ownersCount = 0;
                
                try {
                    // Sync group members individually
                    $membersCount = $this->syncGroupMembersIndividually($accessToken, $groupData['id'], $groupData['displayName']);
                } catch (\Exception $e) {
                }
                
                try {
                    $ownersCount = $this->getGroupOwnersCount($accessToken, $groupData['id']);
                } catch (\Exception $e) {
                }

                $group = SyncedGroup::updateOrCreate(
                    ['external_id' => $groupData['id'], 'user_id' => auth()->id()],
                    [
                        'user_id' => auth()->id(),
                        'platform' => 'microsoft',
                        'display_name' => $groupData['displayName'],
                        'description' => $groupData['description'] ?? null,
                        'email' => $groupData['mail'] ?? null,
                        'group_type' => implode(',', $groupData['groupTypes'] ?? []),
                        'security_enabled' => $groupData['securityEnabled'] ?? false,
                        'mail_enabled' => $groupData['mailEnabled'] ?? false,
                        'members' => $membersCount,
                        'owners' => $ownersCount,
                        'raw_data' => $groupData,
                    ]
                );
                $syncedCount++;
            }

            $syncLog->update(['groups_synced' => $syncedCount]);
            } else {
                throw new \Exception('Microsoft groups sync failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getUserPhoto($accessToken, $userId)
    {
        try {
            // First check if user has a photo
            $photoInfoResponse = Http::withToken($accessToken)
                ->get("https://graph.microsoft.com/v1.0/users/{$userId}/photo");
            
            if ($photoInfoResponse->successful()) {
                $photoInfo = $photoInfoResponse->json();
                
                // If photo exists, get the actual photo data
                if (isset($photoInfo['@odata.mediaContentType'])) {
                    $response = Http::withToken($accessToken)
                        ->get("https://graph.microsoft.com/v1.0/users/{$userId}/photo/\$value");
                    
                    if ($response->successful()) {
                        // Save photo to public directory
                        $photoPath = "assets/images/profile_photos/{$userId}_" . time() . ".jpg";
                        $fullPath = public_path($photoPath);
                        
                        // Create directory if it doesn't exist
                        if (!file_exists(public_path('assets/images/profile_photos'))) {
                            mkdir(public_path('assets/images/profile_photos'), 0755, true);
                        }
                        
                        file_put_contents($fullPath, $response->body());
                        return $photoPath;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silent fail - user might not have a photo (this is normal)
        }
        
        return null;
    }

    private function getUserTeamsPhone($accessToken, $userId)
    {
        try {
            // Get user's Teams phone number from their profile
            $response = Http::withToken($accessToken)
                ->get("https://graph.microsoft.com/v1.0/users/{$userId}", [
                    '$select' => 'businessPhones,mobilePhone'
                ]);
            
            if ($response->successful()) {
                $userData = $response->json();
                return $userData['businessPhones'][0] ?? $userData['mobilePhone'] ?? null;
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }

    private function getUserManager($accessToken, $userId)
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("https://graph.microsoft.com/v1.0/users/{$userId}/manager");
            
            if ($response->successful()) {
                $managerData = $response->json();
                return $managerData['mail'] ?? $managerData['userPrincipalName'] ?? null;
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }

    private function syncDirectories($accessToken, $syncLog)
    {
        try {
            // Get organizational units/directories
            $response = Http::withToken($accessToken)
                ->get('https://graph.microsoft.com/v1.0/directoryObjects', [
                    '$filter' => "objectType eq 'directory'",
                    '$select' => 'id,displayName,description,objectType'
                ]);

            if ($response->successful()) {
                $directories = $response->json()['value'];
                $directoryCount = 0;

                foreach ($directories as $directoryData) {
                    // Store directory information in raw_data for now
                    // You might want to create a separate table for directories
                    $directoryCount++;
                }

            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Check if the authenticated Microsoft account is a personal account (not organizational/tenant).
     * Uses multiple detection methods for reliability.
     */
    private function isPersonalMicrosoftAccount($socialiteUser, $accessToken): bool
    {
        // Personal Microsoft account domains
        $personalDomains = [
            '@outlook.com', '@outlook.co', '@outlook.',
            '@hotmail.com', '@hotmail.co', '@hotmail.',
            '@live.com', '@live.co', '@live.',
            '@msn.com',
            '@windowslive.com',
            '@passport.com',
        ];
        
        // Method 1: Check email domain from Socialite user
        $email = $socialiteUser->email ?? '';
        $emailLower = strtolower($email);
        
        foreach ($personalDomains as $domain) {
            if (strpos($emailLower, $domain) !== false) {
                \Log::info("Personal account detected by email domain: {$email}");
                return true;
            }
        }
        
        // Method 2: Try to call /organization endpoint — fails for personal accounts
        try {
            $token = \is_array($accessToken) && !empty($accessToken['access_token'])
                ? $accessToken['access_token']
                : $accessToken;
                
            $response = Http::withToken($token)
                ->get('https://graph.microsoft.com/v1.0/organization');
            
            if ($response->successful()) {
                $orgData = $response->json();
                $orgs = $orgData['value'] ?? [];
                
                if (empty($orgs)) {
                    \Log::info("Personal account detected: no organization data found for {$email}");
                    return true;
                }
                
                // Has organization data = work/school account
                return false;
            }
            
            // If the response has an error about MSA/personal accounts
            $body = $response->body();
            if (strpos($body, 'unsupported') !== false || 
                strpos($body, 'personal') !== false ||
                strpos($body, 'MSA') !== false ||
                $response->status() === 403) {
                \Log::info("Personal account detected via /organization API response for {$email}");
                return true;
            }
        } catch (\Exception $e) {
            // Organization endpoint failure often indicates personal account
            if (strpos($e->getMessage(), 'MSA') !== false ||
                strpos($e->getMessage(), 'personal') !== false) {
                return true;
            }
        }
        
        // Default: not personal (allow connection to proceed)
        return false;
    }

    private function detectAccountType($accessToken)
    {
        try {
            $token = \is_array($accessToken) && !empty($accessToken['access_token'])
                ? $accessToken['access_token']
                : $accessToken;
            // Try to get organization info - this will fail for personal accounts
            $response = Http::withToken($token)
                ->get('https://graph.microsoft.com/v1.0/organization');
            
            if ($response->successful()) {
                return 'work_school';
            }
        } catch (\Exception $e) {
            // If organization endpoint fails, it's likely a personal account
            if (strpos($e->getMessage(), 'MSA accounts') !== false || 
                strpos($e->getMessage(), 'Insufficient privileges') !== false) {
                return 'personal';
            }
        }
        
        // Try to get user info to determine account type
        try {
            $response = Http::withToken($token)
                ->get('https://graph.microsoft.com/v1.0/me');
            
            if ($response->successful()) {
                $userData = $response->json();
                // Check if user has a work/school domain
                $email = $userData['userPrincipalName'] ?? $userData['mail'] ?? '';
                if (strpos($email, '@outlook.com') !== false || 
                    strpos($email, '@hotmail.com') !== false || 
                    strpos($email, '@live.com') !== false) {
                    return 'personal';
                }
                return 'work_school';
            }
        } catch (\Exception $e) {
        }
        
        return 'unknown';
    }

    private function syncPersonalAccount($accessToken, $user)
    {
        try {
            // Create a basic sync log for personal account
            $syncLog = SyncLog::create([
                'user_id' => auth()->id(),
                'platform' => 'microsoft',
                'sync_type' => 'personal',
                'status' => 'started',
                'started_at' => now(),
            ]);

            // Get basic user info from the authenticated user
            $userData = [
                'id' => $user->id,
                'displayName' => $user->name,
                'mail' => $user->email,
                'userPrincipalName' => $user->email,
                'givenName' => explode(' ', $user->name)[0] ?? null,
                'surname' => explode(' ', $user->name)[1] ?? null,
                'accountEnabled' => true,
            ];

            // Sync only the current user
            SyncedUser::updateOrCreate(
                ['external_id' => $user->id, 'user_id' => auth()->id()],
                [
                    'user_id' => auth()->id(),
                    'platform' => 'microsoft',
                    'email' => $user->email,
                    'display_name' => $user->name,
                    'first_name' => explode(' ', $user->name)[0] ?? null,
                    'last_name' => explode(' ', $user->name)[1] ?? null,
                    'account_enabled' => true,
                    'raw_data' => array_merge($userData, [
                        'account_type' => 'personal',
                        'note' => 'Personal Microsoft Account - limited data available'
                    ]),
                ]
            );

            $syncLog->update([
                'status' => 'completed',
                'users_synced' => 1,
                'groups_synced' => 0,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
        }
    }

    private function getGroupMembersCount($accessToken, $groupId)
    {
        $totalCount = 0;
        $nextLink = "https://graph.microsoft.com/v1.0/groups/{$groupId}/members";
        
        while ($nextLink) {
            $response = Http::withToken($accessToken)
                ->get($nextLink, [
                    '$top' => 100, // Get 100 at a time
                    '$select' => 'id'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $totalCount += count($data['value'] ?? []);
                
                // Check if there's a next page
                $nextLink = $data['@odata.nextLink'] ?? null;
            } else {
                break;
            }
        }
        
        return $totalCount;
    }

    private function getGroupOwnersCount($accessToken, $groupId)
    {
        $totalCount = 0;
        $nextLink = "https://graph.microsoft.com/v1.0/groups/{$groupId}/owners";
        
        while ($nextLink) {
            $response = Http::withToken($accessToken)
                ->get($nextLink, [
                    '$top' => 100, // Get 100 at a time
                    '$select' => 'id'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $totalCount += count($data['value'] ?? []);
                
                // Check if there's a next page
                $nextLink = $data['@odata.nextLink'] ?? null;
            } else {
                break;
            }
        }
        
        return $totalCount;
    }

    private function syncGroupMembersIndividually($accessToken, $groupId, $groupName)
    {
        $syncedCount = 0;
        $nextLink = "https://graph.microsoft.com/v1.0/groups/{$groupId}/members";
        
        
        while ($nextLink) {
            $response = Http::withToken($accessToken)
                ->get($nextLink, [
                    '$top' => 100,
                    '$select' => 'id,displayName,mail,userPrincipalName,givenName,surname,jobTitle,department,officeLocation,businessPhones,mobilePhone,accountEnabled,preferredLanguage,usageLocation,userType,createdDateTime,creationType,externalUserState,identities,assignedLicenses,assignedPlans,provisionedPlans,streetAddress,city,state,postalCode,country,companyName,employeeId,employeeType,employeeHireDate'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $members = $data['value'] ?? [];
                
                foreach ($members as $userData) {
                    // Create a unique external_id for each group-user combination
                    $uniqueExternalId = $groupId . '_' . $userData['id'];
                    
                    // Get user's profile photo
                    $photoUrl = $this->getUserPhoto($accessToken, $userData['id']);
                    
                    $user = SyncedUser::updateOrCreate(
                        ['external_id' => $uniqueExternalId, 'user_id' => auth()->id()],
                        [
                            'user_id' => auth()->id(),
                            'platform' => 'microsoft',
                            'group_id' => $groupId,
                            'group_name' => $groupName,
                            'original_user_id' => $userData['id'], // Store original user ID
                            'email' => $userData['mail'] ?? $userData['userPrincipalName'],
                            'display_name' => $userData['displayName'],
                            'first_name' => $userData['givenName'] ?? null,
                            'last_name' => $userData['surname'] ?? null,
                            'job_title' => $userData['jobTitle'] ?? null,
                            'department' => $userData['department'] ?? null,
                            'phone_number' => $userData['businessPhones'][0] ?? null,
                            'mobile_phone' => $userData['mobilePhone'] ?? null,
                            'office_location' => $userData['officeLocation'] ?? null,
                            'account_enabled' => $userData['accountEnabled'] ?? true,
                            'user_principal_name' => $userData['userPrincipalName'] ?? null,
                            'preferred_language' => $userData['preferredLanguage'] ?? null,
                            'usage_location' => $userData['usageLocation'] ?? null,
                            'user_type' => $userData['userType'] ?? null,
                            'created_date_time' => $userData['createdDateTime'] ?? null,
                            'creation_type' => $userData['creationType'] ?? null,
                            'external_user_state' => $userData['externalUserState'] ?? null,
                            'identities' => $userData['identities'] ?? null,
                            'assigned_licenses' => $userData['assignedLicenses'] ?? null,
                            'assigned_plans' => $userData['assignedPlans'] ?? null,
                            'provisioned_plans' => $userData['provisionedPlans'] ?? null,
                            'street_address' => $userData['streetAddress'] ?? null,
                            'city' => $userData['city'] ?? null,
                            'state' => $userData['state'] ?? null,
                            'postal_code' => $userData['postalCode'] ?? null,
                            'country' => $userData['country'] ?? null,
                            'company_name' => $userData['companyName'] ?? null,
                            'employee_id' => $userData['employeeId'] ?? null,
                            'employee_type' => $userData['employeeType'] ?? null,
                            'employee_hire_date' => $userData['employeeHireDate'] ?? null,
                            'profile_photo_url' => $photoUrl,
                            'raw_data' => $userData,
                        ]
                    );
                    $syncedCount++;
                }
                
                // Check if there's a next page
                $nextLink = $data['@odata.nextLink'] ?? null;
            } else {
                break;
            }
        }
        
        return $syncedCount;
    }
}
