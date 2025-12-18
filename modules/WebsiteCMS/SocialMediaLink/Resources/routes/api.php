<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\SocialMediaLink\Controllers\SocialMediaLinkController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/', [SocialMediaLinkController::class, 'index'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);


Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    Route::get('/types', [SocialMediaLinkController::class, 'getTypes'])
        ->permission(Permission::SOCIAL_MEDIA_LINK_LIST());
    Route::post('/', [SocialMediaLinkController::class, 'store'])
        ->permission(Permission::SOCIAL_MEDIA_LINK_CREATE());
//    Route::post('/export', [SocialMediaLinkController::class, 'export'])
//        ->permission(Permission::SOCIAL_MEDIA_LINK_EXPORT());

    Route::put('/{id}', [SocialMediaLinkController::class, 'update'])
        ->permission(Permission::SOCIAL_MEDIA_LINK_UPDATE());
    Route::put('/{id}/status', [SocialMediaLinkController::class, 'updateStatus'])
        ->permission(Permission::SOCIAL_MEDIA_LINK_ACTIVATE());
    Route::delete('/{id}', [SocialMediaLinkController::class, 'delete'])
        ->permission(Permission::SOCIAL_MEDIA_LINK_DELETE());
});
Route::get('/{id}', [SocialMediaLinkController::class, 'show'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);
