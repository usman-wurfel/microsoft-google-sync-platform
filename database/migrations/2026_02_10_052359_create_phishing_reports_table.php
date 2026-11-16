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
        Schema::create('phishing_reports', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('user_email'); // Reporter
            $table->string('message_id')->index(); // Graph Message ID
            $table->string('internet_message_id')->nullable()->index(); // Needed for purge
            $table->string('subject')->nullable();
            $table->string('sender')->nullable();
            $table->timestamp('received_at')->nullable();
            
            // Analysis Results
            $table->integer('risk_score')->default(0);
            $table->string('verdict')->default('UNKNOWN'); // CLEAN, SPAM, PHISH, MALICIOUS
            $table->json('analysis_details')->nullable(); // Risk flags, URLs
            
            // Actions Taken
            $table->string('action_taken')->nullable(); // MOVED_TO_JUNK, DELETED, PURGED
            $table->timestamp('action_at')->nullable();
            
            // Defender Submission
            $table->string('defender_submission_id')->nullable();
            $table->string('defender_status')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phishing_reports');
    }
};
