<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Modules\Licensing\Http\Middleware\CheckLicenseStatus::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->alias([
            'module.enabled' => \App\Http\Middleware\ModuleEnabledMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request, $exception) => $request->is('api/v1/service/reports/*') || $request->expectsJson());
        $exceptions->respond(function ($response) {
            if (request()->is('api/v1/service/reports/*')) {
                $response->headers->set('Cache-Control', 'no-store, private');
            }
            return $response;
        });
    })->create()
    ->usePublicPath(
        file_exists(dirname(__DIR__, 2).'/public_html') 
            ? dirname(__DIR__, 2).'/public_html' 
            : dirname(__DIR__).'/public'
    );
