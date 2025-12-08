<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoProduct\Controllers\Dashboard\EcoProductDashboardController;
use Modules\Ecommerce\EcoProduct\Controllers\EcoProductController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], 'prefix' => 'dashboard/products'], function () {
    Route::get('/', [EcoProductDashboardController::class, 'index'])
        ->permission(Permission::ECOMMERCE_PRODUCT_LIST());
    Route::post('/', [EcoProductDashboardController::class, 'store'])
        ->permission(Permission::ECOMMERCE_PRODUCT_CREATE());
    Route::post('/export', [EcoProductDashboardController::class, 'export'])
        ->permission(Permission::ECOMMERCE_PRODUCT_EXPORT());

    Route::get('/statistics', [EcoProductDashboardController::class, 'getStatistics'])
        ->permission(Permission::ECOMMERCE_PRODUCT_LIST());

    Route::get('/{id}', [EcoProductDashboardController::class, 'show'])
        ->permission(Permission::ECOMMERCE_PRODUCT_VIEW(), Permission::ECOMMERCE_PRODUCT_UPDATE());
    Route::post('/{id}', [EcoProductDashboardController::class, 'update'])
        ->permission(Permission::ECOMMERCE_PRODUCT_UPDATE());
    Route::patch('/{id}/toggle-visibility', [EcoProductDashboardController::class, 'toggleVisibility'])
        ->permission(Permission::ECOMMERCE_PRODUCT_ACTIVATE());
    Route::delete('/{id}', [EcoProductDashboardController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_PRODUCT_DELETE());
});
