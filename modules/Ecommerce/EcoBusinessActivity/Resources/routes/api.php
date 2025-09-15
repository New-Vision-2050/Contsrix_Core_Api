<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoBusinessActivity\Controllers\EcoBusinessActivityController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [EcoBusinessActivityController::class, 'show']);
    Route::post('/', [EcoBusinessActivityController::class, 'store']);
    Route::post('/export', [EcoBusinessActivityController::class, 'export']);
});
