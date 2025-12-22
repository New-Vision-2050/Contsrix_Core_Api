<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Controllers\CategoryWebsiteCMSController;
use Modules\RoleAndPermission\Enums\Permission;


Route::get('/', [CategoryWebsiteCMSController::class, 'index'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::get('/all', [CategoryWebsiteCMSController::class, 'all'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);


Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/categeory-types', [CategoryWebsiteCMSController::class, 'getCetegoryTypes'])
        ->permission(Permission::CATEGORY_WEBSITE_CMS_LIST());
    Route::post('/', [CategoryWebsiteCMSController::class, 'store'])
        ->permission(Permission::CATEGORY_WEBSITE_CMS_CREATE());
//    Route::post('/export', [CategoryWebsiteCMSController::class, 'export'])
//        ->permission(Permission::CATEGORY_WEBSITE_CMS_EXPORT());


    Route::put('/{id}', [CategoryWebsiteCMSController::class, 'update'])
        ->permission(Permission::CATEGORY_WEBSITE_CMS_UPDATE());
    Route::delete('/{id}', [CategoryWebsiteCMSController::class, 'delete'])
        ->permission(Permission::CATEGORY_WEBSITE_CMS_DELETE());
});
Route::get('/{id}', [CategoryWebsiteCMSController::class, 'show'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);
