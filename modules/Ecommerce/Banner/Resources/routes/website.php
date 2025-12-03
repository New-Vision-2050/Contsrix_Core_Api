<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Banner\Controllers\Website\BannerWebsiteController;
use Modules\Ecommerce\Banner\Controllers\Website\FeatureWebsiteController;
use Modules\Ecommerce\Banner\Controllers\Website\SettingPageWebsiteController;
use Modules\Ecommerce\Banner\Controllers\Website\StoreBranchWebsiteController;

Route::group(['middleware' => [\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Setting Pages Routes
    Route::get('/setting-pages/by-type', [SettingPageWebsiteController::class, 'getByType']);

    // Banner Routes
    Route::get('/banners', [BannerWebsiteController::class, 'index']);
    Route::get('/banners/{id}', [BannerWebsiteController::class, 'show'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

    // Feature Routes
    Route::get('/features', [FeatureWebsiteController::class, 'index']);
    Route::get('/features/{id}', [FeatureWebsiteController::class, 'show'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

    // Store Branches Routes
    Route::get('/store-branches', [StoreBranchWebsiteController::class, 'index']);
    Route::get('/store-branches/{id}', [StoreBranchWebsiteController::class, 'show'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
});

