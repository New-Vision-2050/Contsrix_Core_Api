<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoBrand\Controllers\Dashboard\EcoBrandDashboardController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group([
    'middleware' => [
        'auth:api', 
        \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
    ]
], function () {
    Route::get('/', [EcoBrandDashboardController::class, 'index'])
        ->permission(Permission::ECOMMERCE_ECO_BRAND_LIST());
    Route::post('/', [EcoBrandDashboardController::class, 'store'])
        ->permission(Permission::ECOMMERCE_ECO_BRAND_CREATE());
    Route::post('/export', [EcoBrandDashboardController::class, 'export'])
        ->permission(Permission::ECOMMERCE_ECO_BRAND_EXPORT());
    
    Route::get('/statistics', [EcoBrandDashboardController::class, 'getStatistics'])
        ->permission(Permission::ECOMMERCE_ECO_BRAND_LIST());
    
    Route::get('/{id}', [EcoBrandDashboardController::class, 'show'])
        ->permission(Permission::ECOMMERCE_ECO_BRAND_VIEW(), Permission::ECOMMERCE_ECO_BRAND_UPDATE());
    Route::post('/{id}', [EcoBrandDashboardController::class, 'update'])
        ->permission(Permission::ECOMMERCE_ECO_BRAND_UPDATE());
    Route::patch('/{id}/toggle-active', [EcoBrandDashboardController::class, 'toggleActive'])
        ->permission(Permission::ECOMMERCE_ECO_BRAND_ACTIVATE());
    Route::delete('/{id}', [EcoBrandDashboardController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_ECO_BRAND_DELETE());
});
