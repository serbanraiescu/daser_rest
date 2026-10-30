<?php

namespace App\Http\Middleware;

use App\Modules\Settings\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateServiceReportApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        $expected = (string) config('service_reporting.api_token', '');
        $headers = ['Cache-Control' => 'no-store, private', 'Pragma' => 'no-cache'];

        if (strlen($expected) < 32) {
            return response()->json(['message' => 'API-ul de rapoarte nu este configurat.'], 503, $headers);
        }

        $provided = $request->bearerToken();
        if (!is_string($provided) || !hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Autentificare API invalidă.'], 401, $headers + ['WWW-Authenticate' => 'Bearer']);
        }

        if (!(CompanySetting::first()?->enable_service_module ?? false)) {
            return response()->json(['message' => 'Modulul Service nu este activ.'], 404, $headers);
        }

        $response = $next($request);
        $response->headers->add($headers);

        return $response;
    }
}
