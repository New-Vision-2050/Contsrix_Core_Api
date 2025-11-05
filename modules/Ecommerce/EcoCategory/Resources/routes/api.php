<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoCategory\Controllers\Dashboard\EcoCategoryDashboardController;


Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoCategoryDashboardController::class, 'index']);
    Route::post('/', [EcoCategoryDashboardController::class, 'store']);
    Route::post('/export', [EcoCategoryDashboardController::class, 'export']);
    
    Route::get('/statistics', [EcoCategoryDashboardController::class, 'getStatistics']);
    
    Route::get('/{id}', [EcoCategoryDashboardController::class, 'show']);
    Route::post('/{id}', [EcoCategoryDashboardController::class, 'update']);
    Route::patch('/{id}/toggle-active', [EcoCategoryDashboardController::class, 'toggleActive']);
    Route::delete('/{id}', [EcoCategoryDashboardController::class, 'delete']);
});
