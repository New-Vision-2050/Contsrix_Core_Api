<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Banner\Controllers\BannerController;
use Modules\Ecommerce\Banner\Controllers\SettingPageController;
use Modules\Ecommerce\Banner\Controllers\FeatureController;
use Modules\RoleAndPermission\Enums\Permission;

// Banner Routes
Route::prefix('banners')->middleware(['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [BannerController::class, 'index'])
        ->permission(Permission::ECOMMERCE_BANNER_LIST());
    Route::post('/', [BannerController::class, 'store'])
        ->permission(Permission::ECOMMERCE_BANNER_CREATE());
    Route::post('/export', [BannerController::class, 'export'])
        ->permission(Permission::ECOMMERCE_BANNER_EXPORT());
    Route::get('/{id}', [BannerController::class, 'show'])
        ->permission(Permission::ECOMMERCE_BANNER_VIEW(), Permission::ECOMMERCE_BANNER_UPDATE());
    Route::post('/{id}', [BannerController::class, 'update'])
        ->permission(Permission::ECOMMERCE_BANNER_UPDATE());
    Route::patch('/{id}/toggle-status', [BannerController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_BANNER_ACTIVATE());
    Route::delete('/{id}', [BannerController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_BANNER_DELETE());
});

// Setting Pages Routes
Route::prefix('setting-pages')->middleware(['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [SettingPageController::class, 'index'])
        ->permission(Permission::ECOMMERCE_SETTING_PAGE_LIST());
    Route::post('/upsert', [SettingPageController::class, 'upsert'])
        ->permission(Permission::ECOMMERCE_SETTING_PAGE_CREATE(), Permission::ECOMMERCE_SETTING_PAGE_UPDATE());
    Route::get('/by-type', [SettingPageController::class, 'getByType'])
        ->permission(Permission::ECOMMERCE_SETTING_PAGE_LIST());
    Route::get('/{id}', [SettingPageController::class, 'show'])
        ->permission(Permission::ECOMMERCE_SETTING_PAGE_VIEW(), Permission::ECOMMERCE_SETTING_PAGE_UPDATE());
    Route::patch('/{id}/toggle-status', [SettingPageController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_SETTING_PAGE_ACTIVATE());
    Route::delete('/{id}', [SettingPageController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_SETTING_PAGE_DELETE());
});

// Features Routes
Route::prefix('features')->middleware(['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [FeatureController::class, 'index'])
        ->permission(Permission::ECOMMERCE_FEATURE_LIST());
    Route::post('/', [FeatureController::class, 'store'])
        ->permission(Permission::ECOMMERCE_FEATURE_CREATE());
    Route::post('/export', [FeatureController::class, 'export'])
        ->permission(Permission::ECOMMERCE_FEATURE_EXPORT());
    Route::get('/active', [FeatureController::class, 'getActiveFeatures'])
        ->permission(Permission::ECOMMERCE_FEATURE_LIST());
    Route::get('/{id}', [FeatureController::class, 'show'])
        ->permission(Permission::ECOMMERCE_FEATURE_VIEW(), Permission::ECOMMERCE_FEATURE_UPDATE());
    Route::put('/{id}', [FeatureController::class, 'update'])
        ->permission(Permission::ECOMMERCE_FEATURE_UPDATE());
    Route::patch('/{id}/toggle-status', [FeatureController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_FEATURE_ACTIVATE());
    Route::delete('/{id}', [FeatureController::class, 'destroy'])
        ->permission(Permission::ECOMMERCE_FEATURE_DELETE());
});

// Store Branches Routes
Route::prefix('store-branches')->middleware(['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'index'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_LIST());
    Route::post('/', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'store'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_CREATE());
    Route::get('/types', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'getTypes'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_LIST());
    Route::get('/active', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'getActiveStoreBranches'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_LIST());
    Route::get('/by-type', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'getByType'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_LIST());
    Route::get('/by-country', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'getByCountry'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_LIST());
    Route::get('/search', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'searchByName'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_LIST());
    Route::get('/{id}', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'show'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_VIEW(), Permission::ECOMMERCE_STORE_BRANCH_UPDATE());
    Route::put('/{id}', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'update'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_UPDATE());
    Route::patch('/{id}/toggle-status', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_ACTIVATE());
    Route::delete('/{id}', [\Modules\Ecommerce\Banner\Controllers\StoreBranchController::class, 'destroy'])
        ->permission(Permission::ECOMMERCE_STORE_BRANCH_DELETE());
});
