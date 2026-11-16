<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'platform_name',
        'logo_path',
        'favicon_path',
        'description',
        'version',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the current settings (singleton pattern)
     */
    public static function getCurrent()
    {
        return static::first() ?? static::create([
            'platform_name' => 'User Sync Platform',
            'description' => 'Professional user synchronization platform for Google Workspace and Microsoft Entra ID',
            'version' => '1.0.0',
        ]);
    }
}
