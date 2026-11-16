<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhishingReport extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'tenant_id',
        'user_email',
        'message_id',
        'internet_message_id',
        'subject',
        'sender',
        'received_at',
        'risk_score',
        'verdict',
        'analysis_details',
        'action_taken',
        'action_at',
        'defender_submission_id',
        'defender_status'
    ];
    
    protected $casts = [
        'analysis_details' => 'array',
        'received_at' => 'datetime',
        'action_at' => 'datetime',
    ];
}
