<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Warehous\Controllers\WarehousController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WarehousController::class, 'index'])
        ->permission(Permission::ECOMMERCE_WAREHOUSE_LIST());
    Route::post('/', [WarehousController::class, 'store'])
        ->permission(Permission::ECOMMERCE_WAREHOUSE_CREATE());
    Route::post('/export', [WarehousController::class, 'export'])
        ->permission(Permission::ECOMMERCE_WAREHOUSE_EXPORT());
    Route::get('/statistics', [WarehousController::class, 'getStatistics'])
        ->permission(Permission::ECOMMERCE_WAREHOUSE_LIST());
    Route::get('/{id}', [WarehousController::class, 'show'])
        ->permission(Permission::ECOMMERCE_WAREHOUSE_VIEW(), Permission::ECOMMERCE_WAREHOUSE_UPDATE());
    Route::put('/{id}', [WarehousController::class, 'update'])
        ->permission(Permission::ECOMMERCE_WAREHOUSE_UPDATE());
    Route::delete('/{id}', [WarehousController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_WAREHOUSE_DELETE());
});
