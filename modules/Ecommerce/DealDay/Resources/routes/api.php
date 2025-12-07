<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\DealDay\Controllers\DealDayController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [DealDayController::class, 'index'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_LIST());
    Route::get('/search', [DealDayController::class, 'search'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_LIST());
    Route::post('/', [DealDayController::class, 'store'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_CREATE());
    Route::post('/export', [DealDayController::class, 'export'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_EXPORT());
    
    Route::get('/statistics', [DealDayController::class, 'getStatistics'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_LIST());

    Route::get('/{id}', [DealDayController::class, 'show'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_VIEW(), Permission::ECOMMERCE_DEAL_DAY_UPDATE());
    Route::put('/{id}', [DealDayController::class, 'update'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_UPDATE());
    Route::patch('/{id}/toggle-status', [DealDayController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_ACTIVATE());
    Route::delete('/{id}', [DealDayController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_DEAL_DAY_DELETE());
});
