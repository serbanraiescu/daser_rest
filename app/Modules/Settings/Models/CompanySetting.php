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
        'enable_allergens',
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
        'enable_allergens' => 'boolean',
        'social_links' => 'array',
        'opening_hours' => 'array',
        'vat_rates' => 'array',
        'measurement_units' => 'array',
        'frontend_colors' => 'array',
        'cookie_consent' => 'array',
        'gallery_content' => 'array',
    ];

    /**
     * Obține programul de funcționare grupat și tradus în limba română.
     */
    public function getFormattedOpeningHours(): array
    {
        $openingHours = $this->opening_hours;
        if (empty($openingHours) || !is_array($openingHours)) {
            return [];
        }

        $daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        $translations = [
            'Monday'    => 'Luni',
            'Tuesday'   => 'Marți',
            'Wednesday' => 'Miercuri',
            'Thursday'  => 'Joi',
            'Friday'    => 'Vineri',
            'Saturday'  => 'Sâmbătă',
            'Sunday'    => 'Duminică',
        ];

        $roToEn = [
            'luni'      => 'Monday',
            'marti'     => 'Tuesday',
            'marţi'     => 'Tuesday',
            'miercuri'  => 'Wednesday',
            'joi'       => 'Thursday',
            'vineri'    => 'Friday',
            'sambata'   => 'Saturday',
            'sâmbătă'   => 'Saturday',
            'duminica'  => 'Sunday',
            'duminică'  => 'Sunday',
        ];

        // 1. Map values and normalize hours
        $scheduleMap = [];
        foreach ($openingHours as $slot) {
            $dayInput = trim($slot['day'] ?? '');
            if (empty($dayInput)) continue;

            $normalizedDay = ucfirst(strtolower($dayInput));
            $lookupKey = strtolower($dayInput);
            if (isset($roToEn[$lookupKey])) {
                $normalizedDay = $roToEn[$lookupKey];
            }

            $hours = $slot['hours'] ?? '';
            $normalizedHours = trim($hours);
            if (empty($normalizedHours) || strtolower($normalizedHours) === 'closed' || strtolower($normalizedHours) === 'inchis') {
                $normalizedHours = 'Închis';
            } else {
                if (preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $normalizedHours, $matches)) {
                    $startHour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $startMin = $matches[2];
                    $endHour = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
                    $endMin = $matches[4];
                    $normalizedHours = "{$startHour}:{$startMin} - {$endHour}:{$endMin}";
                }
            }
            
            if (in_array($normalizedDay, $daysOrder)) {
                $scheduleMap[$normalizedDay] = $normalizedHours;
            }
        }

        // 2. Sort according to standard week order and fill missing days as Closed
        $orderedSchedule = [];
        foreach ($daysOrder as $day) {
            if (isset($scheduleMap[$day])) {
                $orderedSchedule[] = [
                    'day' => $day,
                    'hours' => $scheduleMap[$day]
                ];
            } else {
                // If a day is completely missing, default to Closed
                $orderedSchedule[] = [
                    'day' => $day,
                    'hours' => 'Închis'
                ];
            }
        }

        // 3. Group consecutive days with same hours
        $grouped = [];
        $currentGroup = null;

        foreach ($orderedSchedule as $item) {
            $day = $item['day'];
            $hours = $item['hours'];

            if ($currentGroup === null) {
                $currentGroup = [
                    'start_day' => $day,
                    'end_day' => $day,
                    'hours' => $hours,
                    'count' => 1
                ];
            } else {
                if ($currentGroup['hours'] === $hours) {
                    $currentGroup['end_day'] = $day;
                    $currentGroup['count']++;
                } else {
                    $grouped[] = $currentGroup;
                    $currentGroup = [
                        'start_day' => $day,
                        'end_day' => $day,
                        'hours' => $hours,
                        'count' => 1
                    ];
                }
            }
        }

        if ($currentGroup !== null) {
            $grouped[] = $currentGroup;
        }

        // 4. Format output into Romanian labels
        $result = [];
        foreach ($grouped as $group) {
            $startRo = $translations[$group['start_day']] ?? $group['start_day'];
            $endRo = $translations[$group['end_day']] ?? $group['end_day'];
            
            if ($group['count'] === 1) {
                $dayLabel = $startRo;
            } else {
                $dayLabel = $startRo . ' - ' . $endRo;
            }
            
            $result[] = [
                'day' => $dayLabel,
                'hours' => $group['hours']
            ];
        }

        return $result;
    }
}
