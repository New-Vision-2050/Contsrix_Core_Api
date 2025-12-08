<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\FlashDeal\Controllers\FlashDealController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FlashDealController::class, 'index'])
        ->permission(Permission::ECOMMERCE_FLASH_DEAL_LIST());
    Route::post('/', [FlashDealController::class, 'store'])
        ->permission(Permission::ECOMMERCE_FLASH_DEAL_CREATE());
    Route::post('/export', [FlashDealController::class, 'export'])
        ->permission(Permission::ECOMMERCE_FLASH_DEAL_EXPORT());

    Route::get('/{id}', [FlashDealController::class, 'show'])
        ->permission(Permission::ECOMMERCE_FLASH_DEAL_VIEW(), Permission::ECOMMERCE_FLASH_DEAL_UPDATE());
    Route::post('/{id}', [FlashDealController::class, 'update'])
        ->permission(Permission::ECOMMERCE_FLASH_DEAL_UPDATE());
    Route::patch('/{id}/toggle-status', [FlashDealController::class, 'toggleStatus'])
        ->permission(Permission::ECOMMERCE_FLASH_DEAL_ACTIVATE());
    Route::delete('/{id}', [FlashDealController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_FLASH_DEAL_DELETE());
});
