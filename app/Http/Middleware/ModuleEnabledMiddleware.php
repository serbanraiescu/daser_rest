<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\Settings\Models\CompanySetting;
use Symfony\Component\HttpFoundation\Response;

class ModuleEnabledMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if ($module === 'service') {
            $enabled = (bool) (CompanySetting::first()?->enable_service_module ?? false);
            if (!$enabled) {
                abort(404, 'Modulul Service nu este activ.');
            }
        }

        return $next($request);
    }
}
