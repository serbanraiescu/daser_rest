<?php

namespace App\Modules\Licensing\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseStatus extends Model
{
    protected $table = 'license_statuses';

    protected $fillable = [
        'license_key',
        'fingerprint',
        'status',          // active, denied, grace_period
        'message',
        'is_grace_period',
        'last_checked_at',
        'next_check_at',
        'metadata',
    ];

    protected $casts = [
        'is_grace_period' => 'boolean',
        'last_checked_at' => 'datetime',
        'next_check_at' => 'datetime',
        'metadata' => 'array',
    ];
}
