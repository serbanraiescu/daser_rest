<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = \App\Modules\Settings\Models\CompanySetting::first();
        if ($settings && $settings->default_language) {
            app()->setLocale($settings->default_language);
        }

        return $next($request);
    }
}
