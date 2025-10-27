<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Banner\Controllers\BannerController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [BannerController::class, 'index']);
    Route::post('/', [BannerController::class, 'store']);
    Route::post('/export', [BannerController::class, 'export']);

    Route::get('/{id}', [BannerController::class, 'show']);
    Route::post('/{id}', [BannerController::class, 'update']);
    Route::patch('/{id}/toggle-status', [BannerController::class, 'toggleStatus']);
    Route::delete('/{id}', [BannerController::class, 'delete']);
});
