<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteNews\Controllers\WebsiteNewsController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/', [WebsiteNewsController::class, 'index'])->middleware([\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]);

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::post('/', [WebsiteNewsController::class, 'store'])
        ->permission(Permission::WEBSITE_NEWS_CREATE());
    Route::post('/export', [WebsiteNewsController::class, 'export'])
        ->permission(Permission::WEBSITE_NEWS_EXPORT());


    Route::post('/{id}', [WebsiteNewsController::class, 'update'])
        ->permission(Permission::WEBSITE_NEWS_UPDATE());
    Route::delete('/{id}', [WebsiteNewsController::class, 'delete'])
        ->permission(Permission::WEBSITE_NEWS_DELETE());
});
Route::get('/{id}', [WebsiteNewsController::class, 'show'])->middleware([\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]);
