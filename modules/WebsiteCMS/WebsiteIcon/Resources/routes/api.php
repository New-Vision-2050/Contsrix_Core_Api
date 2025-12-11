<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteIcon\Controllers\WebsiteIconController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/', [WebsiteIconController::class, 'index'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::get('/{id}', [WebsiteIconController::class, 'show'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/category-types', [WebsiteIconController::class, 'getCategoryTypes'])
        ->permission(Permission::WEBSITE_ICON_LIST());

    Route::post('/', [WebsiteIconController::class, 'store'])
        ->permission(Permission::WEBSITE_ICON_CREATE());
//    Route::post('/export', [WebsiteIconController::class, 'export'])
//        ->permission(Permission::WEBSITE_ICON_EXPORT());

    Route::put('/{id}', [WebsiteIconController::class, 'update'])
        ->permission(Permission::WEBSITE_ICON_UPDATE());
    Route::delete('/{id}', [WebsiteIconController::class, 'delete'])
        ->permission(Permission::WEBSITE_ICON_DELETE());
});
