<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoBusinessActivity\Controllers\Dashboard\EcoBusinessActivityDashboardController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [EcoBusinessActivityDashboardController::class, 'show']);
    Route::post('/', [EcoBusinessActivityDashboardController::class, 'store']);
    Route::post('/export', [EcoBusinessActivityDashboardController::class, 'export']);
});
