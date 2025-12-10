<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\WebsiteCMS\WebsiteContactMessage\Controllers\WebsiteContactMessageController;
Route::post('/', [WebsiteContactMessageController::class, 'store'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteContactMessageController::class, 'index'])->permission(Permission::WEBSITE_CONTACT_MESSAGE_LIST());
    Route::post('/export', [WebsiteContactMessageController::class, 'export']);

    Route::get('/{id}', [WebsiteContactMessageController::class, 'show'])->permission(Permission::WEBSITE_CONTACT_MESSAGE_UPDATE());
    Route::put('/{id}', [WebsiteContactMessageController::class, 'update'])->permission(Permission::WEBSITE_CONTACT_MESSAGE_UPDATE());
    Route::delete('/{id}', [WebsiteContactMessageController::class, 'delete'])->permission(Permission::WEBSITE_CONTACT_MESSAGE_DELETE());

    Route::post('/{id}/reply', [WebsiteContactMessageController::class, 'reply'])->permission(Permission::WEBSITE_CONTACT_MESSAGE_UPDATE());
});
