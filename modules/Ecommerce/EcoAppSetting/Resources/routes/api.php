<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoAppSetting\Controllers\EcoAppSettingController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoAppSettingController::class, 'index']);
    Route::post('/', [EcoAppSettingController::class, 'store']);
    Route::post('/upsert-theme', [EcoAppSettingController::class, 'upsertTheme']);
    Route::post('/upsert-front-page', [EcoAppSettingController::class, 'upsertFrontPage']);
    Route::post('/upsert-banner', [EcoAppSettingController::class, 'upsertBanner']);
    Route::post('/upsert-product-display', [EcoAppSettingController::class, 'upsertProductDisplay']);
    Route::post('/upsert-favorites', [EcoAppSettingController::class, 'upsertFavorites']);
    Route::post('/upsert-filters', [EcoAppSettingController::class, 'upsertFilters']);
    Route::post('/upsert-product-card', [EcoAppSettingController::class, 'upsertProductCard']);
    Route::post('/upsert-filter-display', [EcoAppSettingController::class, 'upsertFilterDisplay']);
    Route::post('/upsert-terms', [EcoAppSettingController::class, 'upsertTerms']);
    Route::post('/upsert-cart', [EcoAppSettingController::class, 'upsertCart']);
    Route::get('/filters', [EcoAppSettingController::class, 'getFiltersByCompany']);
    Route::get('/company', [EcoAppSettingController::class, 'getByCompany']);
    Route::get('/banner', [EcoAppSettingController::class, 'getBannerByCompany']);
    Route::post('/export', [EcoAppSettingController::class, 'export']);

    Route::get('/{id}', [EcoAppSettingController::class, 'show']);
    Route::put('/{id}', [EcoAppSettingController::class, 'update']);
    Route::delete('/{id}', [EcoAppSettingController::class, 'delete']);
});
