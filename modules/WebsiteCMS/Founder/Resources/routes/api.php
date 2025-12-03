<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\Founder\Controllers\FounderController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FounderController::class, 'index'])
        ->permission(Permission::FOUNDER_LIST());
    Route::post('/', [FounderController::class, 'store'])
        ->permission(Permission::FOUNDER_CREATE());
//    Route::post('/export', [FounderController::class, 'export'])
//        ->permission(Permission::FOUNDER_EXPORT());

    Route::get('/{id}', [FounderController::class, 'show'])
        ->permission(Permission::FOUNDER_UPDATE());
    Route::post('/{id}', [FounderController::class, 'update'])
        ->permission(Permission::FOUNDER_UPDATE());
    Route::delete('/{id}', [FounderController::class, 'delete'])
        ->permission(Permission::FOUNDER_DELETE());
});
