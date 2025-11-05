<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Banner\Controllers\BannerController;
use Modules\Ecommerce\Banner\Controllers\SettingPageController;
use Modules\Ecommerce\Banner\Controllers\FeatureController;

// Banner Routes
Route::prefix('banners')->middleware(['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [BannerController::class, 'index']);
    Route::post('/', [BannerController::class, 'store']);
    Route::post('/export', [BannerController::class, 'export']);
    Route::get('/{id}', [BannerController::class, 'show']);
    Route::post('/{id}', [BannerController::class, 'update']);
    Route::patch('/{id}/toggle-status', [BannerController::class, 'toggleStatus']);
    Route::delete('/{id}', [BannerController::class, 'delete']);
});

// Setting Pages Routes
Route::prefix('setting-pages')->middleware(['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [SettingPageController::class, 'index']);
    Route::post('/upsert', [SettingPageController::class, 'upsert']);
    Route::get('/by-type', [SettingPageController::class, 'getByType']);
    Route::get('/{id}', [SettingPageController::class, 'show']);
    Route::patch('/{id}/toggle-status', [SettingPageController::class, 'toggleStatus']);
    Route::delete('/{id}', [SettingPageController::class, 'delete']);
});

// Features Routes
Route::prefix('features')->middleware(['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [FeatureController::class, 'index']);
    Route::post('/', [FeatureController::class, 'store']);
    Route::post('/export', [FeatureController::class, 'export']);
    Route::get('/active', [FeatureController::class, 'getActiveFeatures']);
    Route::get('/{id}', [FeatureController::class, 'show']);
    Route::put('/{id}', [FeatureController::class, 'update']);
    Route::patch('/{id}/toggle-status', [FeatureController::class, 'toggleStatus']);
    Route::delete('/{id}', [FeatureController::class, 'destroy']);
});

// Store Branches Routes
Route::prefix('store-branches')->middleware(['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'index']);
    Route::post('/', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'store']);
    Route::get('/types', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'getTypes']);
    Route::get('/active', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'getActiveStoreBranches']);
    Route::get('/by-type', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'getByType']);
    Route::get('/by-country', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'getByCountry']);
    Route::get('/search', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'searchByName']);
    Route::get('/{id}', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'show']);
    Route::put('/{id}', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'update']);
    Route::patch('/{id}/toggle-status', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'toggleStatus']);
    Route::delete('/{id}', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'destroy']);
});
