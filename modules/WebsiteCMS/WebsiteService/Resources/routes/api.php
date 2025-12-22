<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteService\Controllers\WebsiteServiceController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/', [WebsiteServiceController::class, 'index'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::get('/{id}', [WebsiteServiceController::class, 'show'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::post('/', [WebsiteServiceController::class, 'store'])
        ->permission(Permission::WEBSITE_SERVICE_CREATE());

    Route::put('/{id}', [WebsiteServiceController::class, 'update'])
        ->permission(Permission::WEBSITE_SERVICE_UPDATE());
    Route::put('/{id}/status', [WebsiteServiceController::class, 'updateStatus'])
        ->permission(Permission::WEBSITE_SERVICE_ACTIVATE());
    Route::delete('/{id}', [WebsiteServiceController::class, 'destroy'])
        ->permission(Permission::WEBSITE_SERVICE_DELETE());
//    Route::post('/export', [WebsiteServiceController::class, 'export'])
//        ->permission(Permission::WEBSITE_SERVICE_EXPORT());
});
