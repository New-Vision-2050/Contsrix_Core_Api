<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoAppSetting\Controllers\Dashboard\EcoAppSettingDashboardController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', action: [EcoAppSettingDashboardController::class, 'index']);
    Route::post('/', [EcoAppSettingDashboardController::class, 'store']);
    Route::post('/upsert-theme', [EcoAppSettingDashboardController::class, 'upsertTheme']);
    Route::post('/upsert-front-page', [EcoAppSettingDashboardController::class, 'upsertFrontPage']);
    Route::post('/upsert-banner', [EcoAppSettingDashboardController::class, 'upsertBanner']);
    Route::post('/upsert-product-display', [EcoAppSettingDashboardController::class, 'upsertProductDisplay']);
    Route::post('/upsert-favorites', [EcoAppSettingDashboardController::class, 'upsertFavorites']);
    Route::post('/upsert-filters', [EcoAppSettingDashboardController::class, 'upsertFilters']);
    Route::post('/upsert-product-card', [EcoAppSettingDashboardController::class, 'upsertProductCard']);
    Route::post('/upsert-filter-display', [EcoAppSettingDashboardController::class, 'upsertFilterDisplay']);
    Route::post('/upsert-terms', [EcoAppSettingDashboardController::class, 'upsertTerms']);
    Route::post('/upsert-cart', [EcoAppSettingDashboardController::class, 'upsertCart']);
    Route::get('/filters', [EcoAppSettingDashboardController::class, 'getFiltersByCompany']);
    Route::get('/company', [EcoAppSettingDashboardController::class, 'getByCompany']);
    Route::get('/banner', [EcoAppSettingDashboardController::class, 'getBannerByCompany']);
    Route::post('/export', [EcoAppSettingDashboardController::class, 'export']);

    Route::get('/{id}', [EcoAppSettingDashboardController::class, 'show']);
    Route::put('/{id}', [EcoAppSettingDashboardController::class, 'update']);
    Route::delete('/{id}', [EcoAppSettingDashboardController::class, 'delete']);
});
