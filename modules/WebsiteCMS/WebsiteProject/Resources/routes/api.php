<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteProject\Controllers\WebsiteProjectController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteProjectController::class, 'index'])
        ->permission(Permission::WEBSITE_PROJECT_LIST());
    Route::post('/', [WebsiteProjectController::class, 'store'])
        ->permission(Permission::WEBSITE_PROJECT_CREATE());
//    Route::post('/export', [WebsiteProjectController::class, 'export'])
//        ->permission(Permission::WEBSITE_PROJECT_EXPORT());

    Route::get('/{id}', [WebsiteProjectController::class, 'show'])
        ->permission(Permission::WEBSITE_PROJECT_UPDATE());
    Route::put('/{id}', [WebsiteProjectController::class, 'update'])
        ->permission(Permission::WEBSITE_PROJECT_UPDATE());
    Route::delete('/{id}', [WebsiteProjectController::class, 'delete'])
        ->permission(Permission::WEBSITE_PROJECT_DELETE());
});
