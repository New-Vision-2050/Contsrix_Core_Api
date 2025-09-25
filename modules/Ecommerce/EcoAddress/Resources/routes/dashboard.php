<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoAddress\Controllers\Dashboard\EcoAddressDashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard API Routes - EcoAddress
|--------------------------------------------------------------------------
|
| Admin dashboard routes for address management
| Requires admin authentication and permissions
|
*/

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], 'prefix' => 'dashboard/addresses'], function () {
    
    Route::get('/', [EcoAddressDashboardController::class, 'index']);
    Route::post('/', [EcoAddressDashboardController::class, 'store']);
    Route::post('/export', [EcoAddressDashboardController::class, 'export']);
    
    Route::get('/{id}', [EcoAddressDashboardController::class, 'show']);
    Route::put('/{id}', [EcoAddressDashboardController::class, 'update']);
    Route::delete('/{id}', [EcoAddressDashboardController::class, 'delete']);
});
