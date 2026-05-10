<?php

namespace App\Modules\Licensing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Licensing\Services\LicenseService;
use Illuminate\Support\Facades\View;

class CheckLicenseStatus
{
    public function __construct(protected LicenseService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Exclude license setup routes and API routes if needed (though API might need protection too)
        if ($request->is('setup/license*', 'livewire/*', 'filament/assets/*')) {
            return $next($request);
        }

        $status = $this->service->getStatus();

        if (!$status || $status->status === 'denied') {
             // Redirect to license page
             return redirect()->to('/setup/license');
        }

        if ($status->is_grace_period || $status->status === 'grace_period') {
            // Share a variable with all views to show the banner
            View::share('is_grace_period', true);
            View::share('license_message', $status->message ?? 'License verification failed. You are in a grace period.');
        }

        return $next($request);
    }
}
