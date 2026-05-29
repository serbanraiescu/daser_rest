<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'site_name',
        'company_logo',
        'company_favicon',
        'hero_title',
        'hero_description',
        'hero_background_image',
        'contact_phone',
        'address',
        'social_links', // JSON: {facebook: '', instagram: ''}
        'opening_hours', // JSON: [{day: 'Monday', hours: '09:00 - 22:00'}]
        'enable_ordering',
        'enable_delivery',
        'hide_ordering_button',
        'enable_service_module',
        'prevent_negative_stock',
        'show_all_products_initially',
        'public_menu_layout',
        'public_menu_hero_style',
        'fiscal_code',
        'trade_register',
        'fiscal_address',
        'spv_token',
        'currency',
        'vat_rates',
        'measurement_units',
        'default_language',
        'frontend_colors',
        'cookie_consent',
        'about_content',
        'terms_content',
        'gdpr_content',
        'privacy_content',
        'gallery_content',
    ];

    protected $casts = [
        'enable_service_module'    => 'boolean',
        'prevent_negative_stock'   => 'boolean',
        'show_all_products_initially' => 'boolean',
        'social_links' => 'array',
        'opening_hours' => 'array',
        'vat_rates' => 'array',
        'measurement_units' => 'array',
        'frontend_colors' => 'array',
        'cookie_consent' => 'array',
        'gallery_content' => 'array',
    ];
}
