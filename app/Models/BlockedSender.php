<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedSender extends Model
{
    protected $fillable = [
        'tenant_id',
        'sender_email',
        'domain',
        'source_phishing_report_id',
        'blocked_at',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(PhishingReport::class, 'source_phishing_report_id');
    }

    /**
     * Extract domain from email (e.g. user@evil.com -> evil.com)
     */
    public static function domainFromEmail(string $email): string
    {
        $part = explode('@', trim($email), 2);
        return $part[1] ?? '';
    }
}
