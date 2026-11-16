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
            // First, drop the unique constraint on email
            try {
                $table->dropUnique('synced_users_email_unique');
            } catch (\Exception $e) {
                // If constraint doesn't exist, continue
            }
            
            // Add new fields
            $table->string('group_id')->nullable()->after('platform');
            $table->string('group_name')->nullable()->after('group_id');
            $table->string('original_user_id')->nullable()->after('external_id');
            
            // Create a compound unique key on external_id and user_id (which already exists)
            // This allows same email to exist multiple times for different groups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('synced_users', function (Blueprint $table) {
            $table->dropColumn(['group_id', 'group_name', 'original_user_id']);
            
            // Re-add the unique constraint on email
            $table->unique('email');
        });
    }
};
