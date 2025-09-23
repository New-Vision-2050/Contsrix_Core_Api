<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoShop\Controllers\Dashboard\EcoShopDashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard API Routes - EcoShop
|--------------------------------------------------------------------------
|
| Admin dashboard routes for shop configuration and management
| Requires admin authentication and permissions
|
*/

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], 'prefix' => 'dashboard/shops'], function () {
    
    Route::get('/', [EcoShopDashboardController::class, 'show']);
    Route::post('/', [EcoShopDashboardController::class, 'store']);
    Route::post('/upsert', [EcoShopDashboardController::class, 'upsert']);
    Route::post('/export', [EcoShopDashboardController::class, 'export']);

    Route::put('/{id}', [EcoShopDashboardController::class, 'update']);
    Route::delete('/{id}', [EcoShopDashboardController::class, 'delete']);
});

