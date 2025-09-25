<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoProduct\Controllers\Dashboard\EcoProductDashboardController;
use Modules\Ecommerce\EcoProduct\Controllers\EcoProductController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], 'prefix' => 'dashboard/products'], function () {
    Route::get('/', [EcoProductDashboardController::class, 'index']);
    Route::post('/', [EcoProductDashboardController::class, 'store']);
    Route::post('/export', [EcoProductDashboardController::class, 'export']);

    Route::get('/statistics', [EcoProductDashboardController::class, 'getStatistics']);

    Route::get('/{id}', [EcoProductDashboardController::class, 'show']);
    Route::post('/{id}', [EcoProductDashboardController::class, 'update']);
    Route::delete('/{id}', [EcoProductDashboardController::class, 'delete']);
});
