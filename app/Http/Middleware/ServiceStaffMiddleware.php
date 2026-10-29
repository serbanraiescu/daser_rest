<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ServiceStaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::get('staff_role') !== 'service') {
            abort(403, 'Această zonă este disponibilă doar personalului de la vulcanizare.');
        }

        return $next($request);
    }
}
