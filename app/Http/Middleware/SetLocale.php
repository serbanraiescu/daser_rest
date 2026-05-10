<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
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
            if ($settings && $settings->default_language) {
                app()->setLocale($settings->default_language);
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet, just continue
        }

        return $next($request);
    }
}
