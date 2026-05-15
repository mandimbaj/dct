<?php

use App\Http\Controllers\Api\V1\HealthDataController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware([ThrottleRequests::using('api'), AuthenticateApiToken::class])
    ->group(function (): void {
        Route::get('/status', [HealthDataController::class, 'status']);
        Route::get('/schema/indicator-values', [HealthDataController::class, 'indicatorValueSchema']);
        Route::get('/locations', [HealthDataController::class, 'locations']);
        Route::get('/indicators', [HealthDataController::class, 'indicators']);
        Route::get('/data-sources', [HealthDataController::class, 'dataSources']);

        Route::get('/indicator-values', [HealthDataController::class, 'indicatorValues']);
        Route::post('/indicator-values', [HealthDataController::class, 'storeIndicatorValue']);
        Route::get('/indicator-values/{record}', [HealthDataController::class, 'showIndicatorValue']);
        Route::put('/indicator-values/{record}', [HealthDataController::class, 'updateIndicatorValue']);
        Route::patch('/indicator-values/{record}', [HealthDataController::class, 'updateIndicatorValue']);
        Route::delete('/indicator-values/{record}', [HealthDataController::class, 'destroyIndicatorValue']);
    });
