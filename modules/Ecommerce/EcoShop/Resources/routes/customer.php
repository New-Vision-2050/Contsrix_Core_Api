<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoShop\Controllers\Customer\EcoShopCustomerController;

/*
|--------------------------------------------------------------------------
| Customer API Routes - EcoShop
|--------------------------------------------------------------------------
|
| Public routes for customers to access shop information
| No authentication required for public shop data
|
*/

Route::group(['prefix' => 'customer/shop'], function () {
    
    // Public shop information
    Route::get('/{company_id}', [EcoShopCustomerController::class, 'show']);
    Route::get('/{company_id}/contact', [EcoShopCustomerController::class, 'getContactInfo']);
    Route::get('/{company_id}/social-media', [EcoShopCustomerController::class, 'getSocialMediaLinks']);
    Route::get('/{company_id}/branding', [EcoShopCustomerController::class, 'getBranding']);
    Route::get('/{company_id}/basic-info', [EcoShopCustomerController::class, 'getBasicInfo']);
});
