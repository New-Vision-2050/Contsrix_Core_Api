<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoCategory\Controllers\Dashboard\EcoCategoryDashboardController;
use Modules\RoleAndPermission\Enums\Permission;


Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoCategoryDashboardController::class, 'index'])
        ->permission(Permission::ECOMMERCE_CATEGORY_LIST());
    Route::post('/', [EcoCategoryDashboardController::class, 'store'])
        ->permission(Permission::ECOMMERCE_CATEGORY_CREATE());
    Route::post('/export', [EcoCategoryDashboardController::class, 'export'])
        ->permission(Permission::ECOMMERCE_CATEGORY_EXPORT());
    
    Route::get('/statistics', [EcoCategoryDashboardController::class, 'getStatistics'])
        ->permission(Permission::ECOMMERCE_CATEGORY_LIST());
    
    Route::get('/{id}', [EcoCategoryDashboardController::class, 'show'])
        ->permission(Permission::ECOMMERCE_CATEGORY_VIEW(), Permission::ECOMMERCE_CATEGORY_UPDATE());
    Route::post('/{id}', [EcoCategoryDashboardController::class, 'update'])
        ->permission(Permission::ECOMMERCE_CATEGORY_UPDATE());
    Route::patch('/{id}/toggle-active', [EcoCategoryDashboardController::class, 'toggleActive'])
        ->permission(Permission::ECOMMERCE_CATEGORY_ACTIVATE());
    Route::delete('/{id}', [EcoCategoryDashboardController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_CATEGORY_DELETE());
});
