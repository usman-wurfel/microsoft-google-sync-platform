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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // 'microsoft' or 'google'
            $table->string('sync_type'); // 'users', 'groups', 'full'
            $table->enum('status', ['started', 'completed', 'failed']);
            $table->integer('users_synced')->default(0);
            $table->integer('groups_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->json('sync_details')->nullable(); // Additional sync information
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['platform', 'status']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
