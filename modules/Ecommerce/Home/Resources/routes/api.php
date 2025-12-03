<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Home\Controllers\HomeController;

Route::group(['middleware' => [\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    Route::get('/banners', [HomeController::class, 'banners']);
    Route::get('/categories', [HomeController::class, 'categories']);
    Route::get('/products/latest', [HomeController::class, 'latestProducts']);
    Route::get('/products/featured', [HomeController::class, 'featuredProducts']);
    Route::get('/products/discounts', [HomeController::class, 'discountedProducts']);
    Route::get('/daily-deal', [HomeController::class, 'dailyDeal']);
    Route::get('/flash-deals', [HomeController::class, 'flashDeals']);
    Route::get('/feature-deals', [HomeController::class, 'featureDeals']);
    Route::get('/offers', [HomeController::class, 'offers']);
    Route::get('/footer', [HomeController::class, 'footer']);

});