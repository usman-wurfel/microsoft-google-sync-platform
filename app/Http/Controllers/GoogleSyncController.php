<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\SyncedUser;
use App\Models\SyncedGroup;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Http;

class GoogleSyncController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/admin.directory.user.readonly',
                'https://www.googleapis.com/auth/admin.directory.group.readonly',
                'https://www.googleapis.com/auth/admin.directory.orgunit.readonly',
                'https://www.googleapis.com/auth/admin.directory.domain.readonly',
                'https://www.googleapis.com/auth/admin.directory.rolemanagement.readonly',
                'https://www.googleapis.com/auth/admin.directory.userschema.readonly',
                'https://www.googleapis.com/auth/admin.directory.customer.readonly',
                'https://www.googleapis.com/auth/admin.directory.resource.calendar.readonly'
            ])
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $user = Socialite::driver('google')->user();
            $accessToken = $user->token;
            
            // Start sync log
            $syncLog = SyncLog::create([
                'user_id' => auth()->id(),
                'platform' => 'google',
                'sync_type' => 'full',
                'status' => 'started',
                'started_at' => now(),
            ]);

            // Skip general users sync, only sync group members
            // $this->syncUsers($accessToken, $syncLog);
            $this->syncGroups($accessToken, $syncLog);
            $this->syncOrgUnits($accessToken, $syncLog);

            // Update sync log
            $syncLog->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return redirect()->route('sync.dashboard')
                ->with('success', 'Google Workspace users and groups synced successfully!');

        } catch (\Exception $e) {
            
            if (isset($syncLog)) {
                $syncLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            return redirect()->route('sync.dashboard')
                ->with('error', 'Failed to sync Google data: ' . $e->getMessage());
        }
    }

    private function syncUsers($accessToken, $syncLog)
    {
        // First, get the domain from the user's profile
        $domain = $this->getUserDomain($accessToken);
        
        if (!$domain) {
            return;
        }

        // Get all users with pagination
        $allUsers = [];
        $nextPageToken = null;
        
        do {
            $params = [
                'domain' => $domain,
                'maxResults' => 500,
                'projection' => 'full',
                'customFieldMask' => '*',
                'viewType' => 'domain_public'
            ];
            
            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }
            
            $response = Http::withToken($accessToken)
                ->get('https://admin.googleapis.com/admin/directory/v1/users', $params);

            if ($response->successful()) {
                $data = $response->json();
                $users = $data['users'] ?? [];
                $allUsers = array_merge($allUsers, $users);
                
                $nextPageToken = $data['nextPageToken'] ?? null;
            } else {
                break;
            }
        } while ($nextPageToken);

        if (!empty($allUsers)) {
            $users = $allUsers;
            $syncedCount = 0;

            foreach ($users as $userData) {
                // Get user's profile photo
                $photoUrl = $this->getUserPhoto($accessToken, $userData['id']);
                
                // Get user's organizational unit
                $orgUnit = $this->getUserOrgUnit($accessToken, $userData['id']);

                $user = SyncedUser::updateOrCreate(
                    ['external_id' => $userData['id'], 'user_id' => auth()->id()],
                    [
                        'user_id' => auth()->id(),
                        'platform' => 'google',
                        'email' => $userData['primaryEmail'],
                        'display_name' => $userData['name']['fullName'] ?? $userData['primaryEmail'],
                        'first_name' => $userData['name']['givenName'] ?? null,
                        'last_name' => $userData['name']['familyName'] ?? null,
                        'job_title' => $userData['organizations'][0]['title'] ?? null,
                        'department' => $userData['organizations'][0]['department'] ?? null,
                        'phone_number' => $userData['phones'][0]['value'] ?? null,
                        'mobile_phone' => $userData['phones'][1]['value'] ?? null,
                        'office_location' => $userData['locations'][0]['area'] ?? null,
                        'account_enabled' => !$userData['suspended'],
                        'last_sign_in' => $userData['lastLoginTime'] ?? null,
                        
                        // Google Workspace specific fields
                        'user_principal_name' => $userData['primaryEmail'],
                        'preferred_language' => $userData['preferredLanguage'] ?? null,
                        'usage_location' => $userData['country'] ?? null,
                        'user_type' => 'google_workspace',
                        'created_date_time' => $userData['creationTime'] ?? null,
                        'creation_type' => 'google_workspace',
                        
                        // Address fields from Google
                        'street_address' => $userData['addresses'][0]['streetAddress'] ?? null,
                        'city' => $userData['addresses'][0]['locality'] ?? null,
                        'state' => $userData['addresses'][0]['region'] ?? null,
                        'postal_code' => $userData['addresses'][0]['postalCode'] ?? null,
                        'country' => $userData['addresses'][0]['country'] ?? null,
                        
                        // Company fields from organizations
                        'company_name' => $userData['organizations'][0]['name'] ?? null,
                        'employee_id' => $userData['externalIds'][0]['value'] ?? null,
                        'employee_type' => $userData['organizations'][0]['type'] ?? null,
                        'employee_hire_date' => $userData['organizations'][0]['startDate'] ?? null,
                        
                        // Communication fields
                        'mail_nickname' => $userData['primaryEmail'],
                        'other_mails' => array_column($userData['emails'] ?? [], 'address'),
                        'im_addresses' => array_column($userData['ims'] ?? [], 'address'),
                        
                        // Profile fields
                        'about_me' => $userData['aboutMe'] ?? null,
                        'interests' => array_column($userData['interests'] ?? [], 'value'),
                        'skills' => array_column($userData['skills'] ?? [], 'value'),
                        
                        // Account settings
                        'show_in_address_list' => !($userData['includeInGlobalAddressList'] ?? true),
                        'preferred_data_location' => $userData['preferredLanguage'] ?? null,
                        
                        // Google specific fields
                        'user_type' => $userData['kind'] ?? 'admin#directory#user',
                        'external_user_state' => $userData['suspended'] ? 'suspended' : 'active',
                        
                        // Profile photo and organizational unit
                        'profile_photo_url' => $photoUrl,
                        'teams_phone' => $this->getGoogleUserPhone($userData),
                        
                        'raw_data' => array_merge($userData, [
                            'profile_photo_url' => $photoUrl,
                            'organizational_unit' => $orgUnit,
                            'domain' => $domain,
                            'google_workspace_data' => true
                        ]),
                    ]
                );
                $syncedCount++;
            }

            $syncLog->update(['users_synced' => $syncedCount]);
        }
    }

    private function syncGroups($accessToken, $syncLog)
    {
        // Get the domain
        $domain = $this->getUserDomain($accessToken);
        
        if (!$domain) {
            return;
        }

        // Get all groups with pagination
        $allGroups = [];
        $nextPageToken = null;
        
        do {
            $params = [
                'domain' => $domain,
                'maxResults' => 500,
                'viewType' => 'domain_public'
            ];
            
            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }
            
            $response = Http::withToken($accessToken)
                ->get('https://admin.googleapis.com/admin/directory/v1/groups', $params);

            if ($response->successful()) {
                $data = $response->json();
                $groups = $data['groups'] ?? [];
                $allGroups = array_merge($allGroups, $groups);
                
                $nextPageToken = $data['nextPageToken'] ?? null;
            } else {
                break;
            }
        } while ($nextPageToken);

        if (!empty($allGroups)) {
            $groups = $allGroups;
            $syncedCount = 0;

            foreach ($groups as $groupData) {
                // Get group members and sync them individually
                $this->syncGoogleGroupMembersIndividually($accessToken, $groupData['id'], $groupData['name']);
                
                // Get group members for count
                $membersResponse = Http::withToken($accessToken)
                    ->get("https://admin.googleapis.com/admin/directory/v1/groups/{$groupData['id']}/members");
                
                $members = [];
                if ($membersResponse->successful()) {
                    $members = collect($membersResponse->json()['members'] ?? [])->pluck('id')->toArray();
                }

                // Get group owners
                $ownersResponse = Http::withToken($accessToken)
                    ->get("https://admin.googleapis.com/admin/directory/v1/groups/{$groupData['id']}/owners");
                
                $owners = [];
                if ($ownersResponse->successful()) {
                    $owners = collect($ownersResponse->json()['owners'] ?? [])->pluck('id')->toArray();
                }

                $group = SyncedGroup::updateOrCreate(
                    ['external_id' => $groupData['id'], 'user_id' => auth()->id()],
                    [
                        'user_id' => auth()->id(),
                        'platform' => 'google',
                        'display_name' => $groupData['name'],
                        'description' => $groupData['description'] ?? null,
                        'email' => $groupData['email'],
                        'group_type' => $groupData['kind'] ?? 'group',
                        'security_enabled' => false, // Google groups don't have this concept
                        'mail_enabled' => true, // Google groups are typically mail-enabled
                        'members' => $members,
                        'owners' => $owners,
                        'raw_data' => array_merge($groupData, [
                            'domain' => $domain,
                            'google_workspace_group' => true,
                            'member_count' => count($members),
                            'owner_count' => count($owners)
                        ]),
                    ]
                );
                $syncedCount++;
            }

            $syncLog->update(['groups_synced' => $syncedCount]);
        }
    }

    private function getUserDomain($accessToken)
    {
        try {
            $response = Http::withToken($accessToken)
                ->get('https://admin.googleapis.com/admin/directory/v1/users/me');
            
            if ($response->successful()) {
                $userData = $response->json();
                $email = $userData['primaryEmail'] ?? $userData['id'];
                return substr(strrchr($email, "@"), 1);
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        
        return null;
    }

    private function getUserPhoto($accessToken, $userId)
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("https://admin.googleapis.com/admin/directory/v1/users/{$userId}/photos/thumbnail");
            
            if ($response->successful()) {
                $photoData = $response->json();
                if (isset($photoData['photoData'])) {
                    // Save photo to public directory
                    $photoPath = "assets/images/profile_photos/{$userId}_" . time() . ".jpg";
                    $fullPath = public_path($photoPath);
                    
                    // Create directory if it doesn't exist
                    if (!file_exists(public_path('assets/images/profile_photos'))) {
                        mkdir(public_path('assets/images/profile_photos'), 0755, true);
                    }
                    
                    file_put_contents($fullPath, base64_decode($photoData['photoData']));
                    return $photoPath;
                }
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        
        return null;
    }

    private function getUserOrgUnit($accessToken, $userId)
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("https://admin.googleapis.com/admin/directory/v1/users/{$userId}");
            
            if ($response->successful()) {
                $userData = $response->json();
                return $userData['orgUnitPath'] ?? null;
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        
        return null;
    }

    private function syncOrgUnits($accessToken, $syncLog)
    {
        try {
            $domain = $this->getUserDomain($accessToken);
            
            if (!$domain) {
                return;
            }

            $response = Http::withToken($accessToken)
                ->get('https://admin.googleapis.com/admin/directory/v1/customers/my_customer/orgunits');

            if ($response->successful()) {
                $orgUnits = $response->json()['organizationUnits'] ?? [];
                $orgUnitCount = 0;

                foreach ($orgUnits as $orgUnitData) {
                    $orgUnitCount++;
                }
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    private function getGoogleUserPhone($userData)
    {
        // Extract phone numbers from Google user data
        $phones = $userData['phones'] ?? [];
        foreach ($phones as $phone) {
            if (isset($phone['type']) && in_array($phone['type'], ['work', 'business'])) {
                return $phone['value'] ?? null;
            }
        }
        
        // Fallback to first phone number
        return $phones[0]['value'] ?? null;
    }

    private function syncGoogleGroupMembersIndividually($accessToken, $groupId, $groupName)
    {
        $syncedCount = 0;
        $nextPageToken = null;
        
        do {
            $params = [
                'maxResults' => 100
            ];
            
            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }
            
            $response = Http::withToken($accessToken)
                ->get("https://admin.googleapis.com/admin/directory/v1/groups/{$groupId}/members", $params);
            
            if ($response->successful()) {
                $data = $response->json();
                $members = $data['members'] ?? [];
                
                foreach ($members as $memberData) {
                    // Get full user details
                    $userResponse = Http::withToken($accessToken)
                        ->get("https://admin.googleapis.com/admin/directory/v1/users/{$memberData['id']}", [
                            'projection' => 'full',
                            'customFieldMask' => '*',
                            'viewType' => 'domain_public'
                        ]);
                    
                    if ($userResponse->successful()) {
                        $userData = $userResponse->json();
                        
                        // Get user's profile photo
                        $photoUrl = $this->getUserPhoto($accessToken, $userData['id']);
                        
                        // Get user's organizational unit
                        $orgUnit = $this->getUserOrgUnit($accessToken, $userData['id']);
                        
                        // Create a unique external_id for each group-user combination
                        $uniqueExternalId = $groupId . '_' . $userData['id'];
                        
                        $user = SyncedUser::updateOrCreate(
                            ['external_id' => $uniqueExternalId, 'user_id' => auth()->id()],
                            [
                                'user_id' => auth()->id(),
                                'platform' => 'google',
                                'group_id' => $groupId,
                                'group_name' => $groupName,
                                'original_user_id' => $userData['id'],
                                'email' => $userData['primaryEmail'],
                                'display_name' => $userData['name']['fullName'] ?? $userData['primaryEmail'],
                                'first_name' => $userData['name']['givenName'] ?? null,
                                'last_name' => $userData['name']['familyName'] ?? null,
                                'job_title' => $userData['organizations'][0]['title'] ?? null,
                                'department' => $userData['organizations'][0]['department'] ?? null,
                                'phone_number' => $userData['phones'][0]['value'] ?? null,
                                'mobile_phone' => $userData['phones'][1]['value'] ?? null,
                                'office_location' => $userData['locations'][0]['area'] ?? null,
                                'account_enabled' => !$userData['suspended'],
                                'last_sign_in' => $userData['lastLoginTime'] ?? null,
                                'user_principal_name' => $userData['primaryEmail'],
                                'preferred_language' => $userData['preferredLanguage'] ?? null,
                                'usage_location' => $userData['country'] ?? null,
                                'user_type' => 'google_workspace',
                                'created_date_time' => $userData['creationTime'] ?? null,
                                'creation_type' => 'google_workspace',
                                'street_address' => $userData['addresses'][0]['streetAddress'] ?? null,
                                'city' => $userData['addresses'][0]['locality'] ?? null,
                                'state' => $userData['addresses'][0]['region'] ?? null,
                                'postal_code' => $userData['addresses'][0]['postalCode'] ?? null,
                                'country' => $userData['addresses'][0]['country'] ?? null,
                                'company_name' => $userData['organizations'][0]['name'] ?? null,
                                'employee_id' => $userData['externalIds'][0]['value'] ?? null,
                                'employee_type' => $userData['organizations'][0]['type'] ?? null,
                                'employee_hire_date' => $userData['organizations'][0]['startDate'] ?? null,
                                'mail_nickname' => $userData['primaryEmail'],
                                'other_mails' => array_column($userData['emails'] ?? [], 'address'),
                                'im_addresses' => array_column($userData['ims'] ?? [], 'address'),
                                'about_me' => $userData['aboutMe'] ?? null,
                                'interests' => array_column($userData['interests'] ?? [], 'value'),
                                'skills' => array_column($userData['skills'] ?? [], 'value'),
                                'show_in_address_list' => !($userData['includeInGlobalAddressList'] ?? true),
                                'preferred_data_location' => $userData['preferredLanguage'] ?? null,
                                'user_type' => $userData['kind'] ?? 'admin#directory#user',
                                'external_user_state' => $userData['suspended'] ? 'suspended' : 'active',
                                'profile_photo_url' => $photoUrl,
                                'teams_phone' => $this->getGoogleUserPhone($userData),
                                'raw_data' => array_merge($userData, [
                                    'profile_photo_url' => $photoUrl,
                                    'organizational_unit' => $orgUnit,
                                    'google_workspace_group_member' => true
                                ]),
                            ]
                        );
                        $syncedCount++;
                    }
                }
                
                $nextPageToken = $data['nextPageToken'] ?? null;
            } else {
                break;
            }
        } while ($nextPageToken);
        
        return $syncedCount;
    }
}
