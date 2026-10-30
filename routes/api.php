<?php

use App\Http\Middleware\AuthenticateServiceReportApi;
use App\Modules\Service\Http\Controllers\ServiceReportApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/service/reports')
    ->middleware([AuthenticateServiceReportApi::class, 'throttle:30,1'])
    ->group(function () {
        Route::get('/daily', [ServiceReportApiController::class, 'daily'])->name('api.service.reports.daily');
        Route::get('/monthly', [ServiceReportApiController::class, 'monthly'])->name('api.service.reports.monthly');
    });
