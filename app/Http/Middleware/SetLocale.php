<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['ro', 'en', 'de', 'it', 'fr'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('setup/license*', '__deploy/*')) {
            return $next($request);
        }

        try {
            $settings = \App\Modules\Settings\Models\CompanySetting::first();
            $defaultLocale = $settings?->default_language ?: 'ro';
            $requestedLocale = strtolower((string) $request->query('lang', ''));

            if (in_array($requestedLocale, self::SUPPORTED_LOCALES, true)) {
                $request->session()->put('locale', $requestedLocale);
            }

            $locale = $request->session()->get('locale', $defaultLocale);
            app()->setLocale(in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'ro');
        } catch (\Exception $e) {
            // Table doesn't exist yet, just continue
        }

        return $next($request);
    }
}
