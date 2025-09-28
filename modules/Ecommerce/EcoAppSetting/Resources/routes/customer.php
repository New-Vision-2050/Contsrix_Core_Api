<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoAppSetting\Controllers\Customer\EcoAppSettingCustomerController;

/*
|--------------------------------------------------------------------------
| Customer API Routes - EcoAppSetting
|--------------------------------------------------------------------------
|
| Public routes for customers to access app settings
| No authentication required for public app configuration
|
*/

Route::group(['prefix' => 'customer/app-settings'], function () {
    
    // Main app settings
    Route::get('/{company_id}', [EcoAppSettingCustomerController::class, 'getAppSettings']);
    Route::get('/{company_id}/config', [EcoAppSettingCustomerController::class, 'getAppConfig']);
    
    // Specific setting groups
    Route::get('/{company_id}/theme', [EcoAppSettingCustomerController::class, 'getThemeSettings']);
    Route::get('/{company_id}/product-display', [EcoAppSettingCustomerController::class, 'getProductDisplaySettings']);
    Route::get('/{company_id}/cart', [EcoAppSettingCustomerController::class, 'getCartSettings']);
    Route::get('/{company_id}/filters', [EcoAppSettingCustomerController::class, 'getFilterSettings']);
    Route::get('/{company_id}/favorites', [EcoAppSettingCustomerController::class, 'getFavoritesSettings']);
    Route::get('/{company_id}/product-card', [EcoAppSettingCustomerController::class, 'getProductCardSettings']);
});
