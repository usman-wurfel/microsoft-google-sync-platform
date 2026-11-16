<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SyncedGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'external_id',
        'display_name',
        'description',
        'email',
        'group_type',
        'security_enabled',
        'mail_enabled',
        'members',
        'owners',
        'raw_data',
    ];

    protected $casts = [
        'security_enabled' => 'boolean',
        'mail_enabled' => 'boolean',
        'members' => 'array',
        'owners' => 'array',
        'raw_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
