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
        Schema::create('blocked_senders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('sender_email')->index();
            $table->string('domain')->nullable()->index();
            $table->unsignedBigInteger('source_phishing_report_id')->nullable();
            $table->timestamp('blocked_at')->useCurrent();
            $table->timestamps();
            $table->unique(['tenant_id', 'sender_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_senders');
    }
};
