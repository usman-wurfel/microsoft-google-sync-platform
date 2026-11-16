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
        Schema::create('synced_groups', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // 'microsoft' or 'google'
            $table->string('external_id')->unique(); // ID from the external platform
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->string('email')->nullable(); // Group email if available
            $table->string('group_type')->nullable(); // Security, Distribution, etc.
            $table->boolean('security_enabled')->default(false);
            $table->boolean('mail_enabled')->default(false);
            $table->json('members'); // Array of member IDs
            $table->json('owners'); // Array of owner IDs
            $table->json('raw_data'); // Store complete group data from API
            $table->timestamps();
            
            $table->index(['platform', 'external_id']);
            $table->index('display_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('synced_groups');
    }
};
