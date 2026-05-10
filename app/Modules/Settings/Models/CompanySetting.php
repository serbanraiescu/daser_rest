<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'site_name',
        'company_logo',
        'hero_title',
        'hero_description',
        'hero_background_image',
        'contact_phone',
        'address',
        'social_links', // JSON: {facebook: '', instagram: ''}
        'opening_hours', // JSON: [{day: 'Monday', hours: '09:00 - 22:00'}]
        'enable_ordering',
        'enable_delivery',
        'fiscal_code',
        'trade_register',
        'fiscal_address',
        'spv_token',
        'currency',
        'vat_rates',
        'measurement_units',
        'default_language',
    ];

    protected $casts = [
        'social_links' => 'array',
        'opening_hours' => 'array',
        'vat_rates' => 'array',
        'measurement_units' => 'array',
    ];
}
