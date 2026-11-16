<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SyncedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'external_id',
        'group_id',
        'group_name',
        'original_user_id',
        'email',
        'display_name',
        'first_name',
        'last_name',
        'job_title',
        'department',
        'phone_number',
        'mobile_phone',
        'office_location',
        'manager_email',
        'account_enabled',
        'last_sign_in',
        'raw_data',
        // Microsoft Graph specific fields
        'user_principal_name',
        'preferred_language',
        'usage_location',
        'user_type',
        'on_premises_sync_enabled',
        'on_premises_last_sync_date_time',
        'created_date_time',
        'creation_type',
        'external_user_state',
        'identities',
        'assigned_licenses',
        'assigned_plans',
        'provisioned_plans',
        // Address fields
        'street_address',
        'city',
        'state',
        'postal_code',
        'country',
        // Company fields
        'company_name',
        'employee_id',
        'employee_type',
        'employee_hire_date',
        // On-premises fields
        'on_premises_distinguished_name',
        'on_premises_domain_name',
        'on_premises_security_identifier',
        'on_premises_sam_account_name',
        'on_premises_user_principal_name',
        'on_premises_extension_attributes',
        // Communication fields
        'proxy_addresses',
        'im_addresses',
        'mail_nickname',
        'other_mails',
        // Profile fields
        'about_me',
        'interests',
        'past_projects',
        'responsibilities',
        'schools',
        'skills',
        // Account settings
        'password_policies',
        'password_profile',
        'preferred_data_location',
        'show_in_address_list',
        // Demographics
        'age_group',
        'consent_provided_for_minor',
        'legal_age_group_classification',
        // Additional data
        'license_assignment_states',
        'on_premises_provisioning_errors',
        // Profile photo
        'profile_photo_url',
        'teams_phone',
    ];

    protected $casts = [
        'account_enabled' => 'boolean',
        'last_sign_in' => 'datetime',
        'raw_data' => 'array',
        'on_premises_sync_enabled' => 'boolean',
        'on_premises_last_sync_date_time' => 'datetime',
        'created_date_time' => 'datetime',
        'employee_hire_date' => 'date',
        'show_in_address_list' => 'boolean',
        'consent_provided_for_minor' => 'boolean',
        'identities' => 'array',
        'assigned_licenses' => 'array',
        'assigned_plans' => 'array',
        'provisioned_plans' => 'array',
        'on_premises_extension_attributes' => 'array',
        'proxy_addresses' => 'array',
        'im_addresses' => 'array',
        'other_mails' => 'array',
        'interests' => 'array',
        'past_projects' => 'array',
        'responsibilities' => 'array',
        'schools' => 'array',
        'skills' => 'array',
        'password_policies' => 'array',
        'password_profile' => 'array',
        'license_assignment_states' => 'array',
        'on_premises_provisioning_errors' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
