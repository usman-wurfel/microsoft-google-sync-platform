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
        Schema::create('synced_users', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // 'microsoft' or 'google'
            $table->string('external_id')->unique(); // ID from the external platform
            $table->string('email')->unique();
            $table->string('display_name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('office_location')->nullable();
            $table->string('manager_email')->nullable();
            $table->boolean('account_enabled')->default(true);
            $table->timestamp('last_sign_in')->nullable();
            $table->json('raw_data'); // Store complete user data from API
            $table->timestamps();
            
            $table->index(['platform', 'external_id']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('synced_users');
    }
};
