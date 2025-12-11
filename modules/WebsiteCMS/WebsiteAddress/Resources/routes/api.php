<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteAddress\Controllers\WebsiteAddressController;
use Modules\RoleAndPermission\Enums\Permission;


Route::get('/', [WebsiteAddressController::class, 'index'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);
Route::get('/{id}', [WebsiteAddressController::class, 'show'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);
Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::post('/', [WebsiteAddressController::class, 'store'])
        ->permission(Permission::WEBSITE_ADDRESS_CREATE());
//    Route::post('/export', [WebsiteAddressController::class, 'export'])
//        ->permission(Permission::WEBSITE_ADDRESS_EXPORT());


    Route::put('/{id}', [WebsiteAddressController::class, 'update'])
        ->permission(Permission::WEBSITE_ADDRESS_UPDATE());
    Route::delete('/{id}', [WebsiteAddressController::class, 'delete'])
        ->permission(Permission::WEBSITE_ADDRESS_DELETE());
});
