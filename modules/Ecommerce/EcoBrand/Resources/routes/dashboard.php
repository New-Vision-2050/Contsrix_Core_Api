<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoBrand\Controllers\Dashboard\EcoBrandDashboardController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoBrandDashboardController::class, 'index']);
    Route::post('/', [EcoBrandDashboardController::class, 'store']);
    
    Route::get('/statistics', [EcoBrandDashboardController::class, 'getStatistics']);
    
    Route::get('/{id}', [EcoBrandDashboardController::class, 'show']);
    Route::post('/{id}', [EcoBrandDashboardController::class, 'update']);
    Route::patch('/{id}/toggle-active', [EcoBrandDashboardController::class, 'toggleActive']);
    Route::delete('/{id}', [EcoBrandDashboardController::class, 'delete']);
});
