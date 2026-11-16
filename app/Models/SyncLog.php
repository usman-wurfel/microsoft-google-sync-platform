<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'sync_type',
        'status',
        'users_synced',
        'groups_synced',
        'error_message',
        'sync_details',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'users_synced' => 'integer',
        'groups_synced' => 'integer',
        'sync_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
