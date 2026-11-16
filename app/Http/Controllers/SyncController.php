<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SyncedUser;
use App\Models\SyncedGroup;
use App\Models\SyncLog;

class SyncController extends Controller
{
    public function dashboard()
    {
        $currentUser = auth()->user();
        
        // If user is admin, show all data. Otherwise, show only their own data
        if ($currentUser->hasRole('admin')) {
            $users = SyncedUser::with('user')->orderBy('created_at', 'desc')->paginate(20);
            $groups = SyncedGroup::with('user')->orderBy('created_at', 'desc')->paginate(20);
            $syncLogs = SyncLog::with('user')->orderBy('started_at', 'desc')->limit(10)->get();
            
            $stats = [
                'total_users' => SyncedUser::count(),
                'total_groups' => SyncedGroup::count(),
                'microsoft_users' => SyncedUser::where('platform', 'microsoft')->count(),
                'google_users' => SyncedUser::where('platform', 'google')->count(),
                'microsoft_groups' => SyncedGroup::where('platform', 'microsoft')->count(),
                'google_groups' => SyncedGroup::where('platform', 'google')->count(),
            ];
        } else {
            // Non-admin users can only see their own synced data
            $users = SyncedUser::where('user_id', $currentUser->id)->orderBy('created_at', 'desc')->paginate(20);
            $groups = SyncedGroup::where('user_id', $currentUser->id)->orderBy('created_at', 'desc')->paginate(20);
            $syncLogs = SyncLog::where('user_id', $currentUser->id)->orderBy('started_at', 'desc')->limit(10)->get();
            
            $stats = [
                'total_users' => SyncedUser::where('user_id', $currentUser->id)->count(),
                'total_groups' => SyncedGroup::where('user_id', $currentUser->id)->count(),
                'microsoft_users' => SyncedUser::where('user_id', $currentUser->id)->where('platform', 'microsoft')->count(),
                'google_users' => SyncedUser::where('user_id', $currentUser->id)->where('platform', 'google')->count(),
                'microsoft_groups' => SyncedGroup::where('user_id', $currentUser->id)->where('platform', 'microsoft')->count(),
                'google_groups' => SyncedGroup::where('user_id', $currentUser->id)->where('platform', 'google')->count(),
            ];
        }

        return view('sync.dashboard', compact('users', 'groups', 'syncLogs', 'stats'));
    }

    public function exportJson()
    {
        $currentUser = auth()->user();
        
        // If user is admin, export all data. Otherwise, export only their own data
        if ($currentUser->hasRole('admin')) {
            $data = [
                'users' => SyncedUser::with('user')->get(),
                'groups' => SyncedGroup::with('user')->get(),
                'exported_at' => now()->toISOString(),
                'exported_by' => $currentUser->name . ' (Admin)',
            ];
        } else {
            $data = [
                'users' => SyncedUser::where('user_id', $currentUser->id)->get(),
                'groups' => SyncedGroup::where('user_id', $currentUser->id)->get(),
                'exported_at' => now()->toISOString(),
                'exported_by' => $currentUser->name,
            ];
        }

        $filename = 'sync_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    public function showUser(SyncedUser $user)
    {
        $currentUser = auth()->user();
        
        // Check if user can view this synced user
        if (!$currentUser->hasRole('admin') && $user->user_id !== $currentUser->id) {
            abort(403, 'You can only view your own synced users.');
        }
        
        return view('users.show', compact('user'));
    }

    public function exportUsers()
    {
        $currentUser = auth()->user();
        
        // If user is admin, export all users. Otherwise, export only their own users
        if ($currentUser->hasRole('admin')) {
            $users = SyncedUser::with('user')->get();
        } else {
            $users = SyncedUser::where('user_id', $currentUser->id)->get();
        }
        
        $data = [
            'export_type' => 'users',
            'total_count' => $users->count(),
            'exported_at' => now()->toISOString(),
            'exported_by' => $currentUser->hasRole('admin') ? $currentUser->name . ' (Admin)' : $currentUser->name,
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'platform' => $user->platform,
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
                    'manager_email' => $user->manager_email,
                    'account_enabled' => $user->account_enabled,
                    'last_sign_in' => $user->last_sign_in,
                    'user_principal_name' => $user->user_principal_name,
                    'preferred_language' => $user->preferred_language,
                    'usage_location' => $user->usage_location,
                    'user_type' => $user->user_type,
                    'created_date_time' => $user->created_date_time,
                    'creation_type' => $user->creation_type,
                    'external_user_state' => $user->external_user_state,
                    'street_address' => $user->street_address,
                    'city' => $user->city,
                    'state' => $user->state,
                    'postal_code' => $user->postal_code,
                    'country' => $user->country,
                    'company_name' => $user->company_name,
                    'employee_id' => $user->employee_id,
                    'employee_type' => $user->employee_type,
                    'employee_hire_date' => $user->employee_hire_date,
                    'mail_nickname' => $user->mail_nickname,
                    'other_mails' => $user->other_mails,
                    'im_addresses' => $user->im_addresses,
                    'about_me' => $user->about_me,
                    'interests' => $user->interests,
                    'skills' => $user->skills,
                    'show_in_address_list' => $user->show_in_address_list,
                    'preferred_data_location' => $user->preferred_data_location,
                    'profile_photo_url' => $user->profile_photo_url,
                    'teams_phone' => $user->teams_phone,
                    'synced_by' => $user->user->name ?? 'Unknown',
                    'synced_at' => $user->created_at,
                    'raw_data' => $user->raw_data
                ];
            })
        ];

        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        return response()->json($data, 200, [], JSON_PRETTY_PRINT)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    public function exportGroups()
    {
        $currentUser = auth()->user();
        
        // If user is admin, export all groups. Otherwise, export only their own groups
        if ($currentUser->hasRole('admin')) {
            $groups = SyncedGroup::with('user')->get();
        } else {
            $groups = SyncedGroup::where('user_id', $currentUser->id)->get();
        }
        
        $data = [
            'export_type' => 'groups',
            'total_count' => $groups->count(),
            'exported_at' => now()->toISOString(),
            'exported_by' => $currentUser->hasRole('admin') ? $currentUser->name . ' (Admin)' : $currentUser->name,
            'groups' => $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'platform' => $group->platform,
                    'external_id' => $group->external_id,
                    'display_name' => $group->display_name,
                    'description' => $group->description,
                    'email' => $group->email,
                    'group_type' => $group->group_type,
                    'security_enabled' => $group->security_enabled,
                    'mail_enabled' => $group->mail_enabled,
                    'members' => $group->members,
                    'owners' => $group->owners,
                    'member_count' => count($group->members ?? []),
                    'owner_count' => count($group->owners ?? []),
                    'synced_by' => $group->user->name ?? 'Unknown',
                    'synced_at' => $group->created_at,
                    'raw_data' => $group->raw_data
                ];
            })
        ];

        $filename = 'groups_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        return response()->json($data, 200, [], JSON_PRETTY_PRINT)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }
}
