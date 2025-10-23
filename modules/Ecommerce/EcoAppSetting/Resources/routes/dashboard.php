<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoAppSetting\Controllers\Dashboard\EcoAppSettingDashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard API Routes - EcoAppSetting
|--------------------------------------------------------------------------
|
| Admin dashboard routes for app settings management
| Requires admin authentication and permissions
|
*/

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], 'prefix' => 'dashboard/app-settings'], function () {
    
    // CRUD operations
    Route::get('/', [EcoAppSettingDashboardController::class, 'index']);
    Route::post('/', [EcoAppSettingDashboardController::class, 'store']);
    Route::get('/{id}', [EcoAppSettingDashboardController::class, 'show']);
    Route::put('/{id}', [EcoAppSettingDashboardController::class, 'update']);
    Route::delete('/{id}', [EcoAppSettingDashboardController::class, 'delete']);
    
    // Company-specific settings
    Route::get('/company/settings', [EcoAppSettingDashboardController::class, 'getCompanySettings']);
    Route::put('/company/settings', [EcoAppSettingDashboardController::class, 'updateCompanySettings']);
    Route::post('/company/reset-default', [EcoAppSettingDashboardController::class, 'resetToDefault']);
    
    // Analytics and statistics
    Route::get('/statistics', [EcoAppSettingDashboardController::class, 'getStatistics']);
    
    // Export functionality
    Route::post('/export', [EcoAppSettingDashboardController::class, 'export']);
});
