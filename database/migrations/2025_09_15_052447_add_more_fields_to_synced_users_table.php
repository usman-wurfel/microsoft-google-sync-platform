<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('synced_users', function (Blueprint $table) {
            // Microsoft Graph specific fields
            $table->string('user_principal_name')->nullable();
            $table->string('preferred_language')->nullable();
            $table->string('usage_location')->nullable();
            $table->string('user_type')->nullable();
            $table->boolean('on_premises_sync_enabled')->nullable();
            $table->timestamp('on_premises_last_sync_date_time')->nullable();
            $table->timestamp('created_date_time')->nullable();
            $table->string('creation_type')->nullable();
            $table->string('external_user_state')->nullable();
            $table->json('identities')->nullable();
            $table->json('assigned_licenses')->nullable();
            $table->json('assigned_plans')->nullable();
            $table->json('provisioned_plans')->nullable();
            
            // Address fields
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            
            // Company fields
            $table->string('company_name')->nullable();
            $table->string('employee_id')->nullable();
            $table->string('employee_type')->nullable();
            $table->date('employee_hire_date')->nullable();
            
            // On-premises fields
            $table->string('on_premises_distinguished_name')->nullable();
            $table->string('on_premises_domain_name')->nullable();
            $table->string('on_premises_security_identifier')->nullable();
            $table->string('on_premises_sam_account_name')->nullable();
            $table->string('on_premises_user_principal_name')->nullable();
            $table->json('on_premises_extension_attributes')->nullable();
            
            // Communication fields
            $table->json('proxy_addresses')->nullable();
            $table->json('im_addresses')->nullable();
            $table->string('mail_nickname')->nullable();
            $table->json('other_mails')->nullable();
            
            // Profile fields
            $table->text('about_me')->nullable();
            $table->json('interests')->nullable();
            $table->json('past_projects')->nullable();
            $table->json('responsibilities')->nullable();
            $table->json('schools')->nullable();
            $table->json('skills')->nullable();
            
            // Account settings
            $table->json('password_policies')->nullable();
            $table->json('password_profile')->nullable();
            $table->string('preferred_data_location')->nullable();
            $table->boolean('show_in_address_list')->nullable();
            
            // Demographics
            $table->string('age_group')->nullable();
            $table->boolean('consent_provided_for_minor')->nullable();
            $table->string('legal_age_group_classification')->nullable();
            
            // Additional data
            $table->json('license_assignment_states')->nullable();
            $table->json('on_premises_provisioning_errors')->nullable();
            
            // Profile photo
            $table->string('profile_photo_url')->nullable();
            $table->string('teams_phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('synced_users', function (Blueprint $table) {
            $table->dropColumn([
                'user_principal_name', 'preferred_language', 'usage_location', 'user_type',
                'on_premises_sync_enabled', 'on_premises_last_sync_date_time', 'created_date_time',
                'creation_type', 'external_user_state', 'identities', 'assigned_licenses',
                'assigned_plans', 'provisioned_plans', 'street_address', 'city', 'state',
                'postal_code', 'country', 'company_name', 'employee_id', 'employee_type',
                'employee_hire_date', 'on_premises_distinguished_name', 'on_premises_domain_name',
                'on_premises_security_identifier', 'on_premises_sam_account_name',
                'on_premises_user_principal_name', 'on_premises_extension_attributes',
                'proxy_addresses', 'im_addresses', 'mail_nickname', 'other_mails',
                'about_me', 'interests', 'past_projects', 'responsibilities', 'schools',
                'skills', 'password_policies', 'password_profile', 'preferred_data_location',
                'show_in_address_list', 'age_group', 'consent_provided_for_minor',
                'legal_age_group_classification', 'license_assignment_states',
                'on_premises_provisioning_errors', 'profile_photo_url', 'teams_phone'
            ]);
        });
    }
};
