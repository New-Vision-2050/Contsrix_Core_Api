<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteProjectSetting\Controllers\WebsiteProjectSettingController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/', [WebsiteProjectSettingController::class, 'index'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::get('/{id}', [WebsiteProjectSettingController::class, 'show'])
    ->middleware([
        \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
    ]);
Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    Route::post('/', [WebsiteProjectSettingController::class, 'store'])
        ->permission(Permission::WEBSITE_PROJECT_SETTING_CREATE());
    Route::post('/export', [WebsiteProjectSettingController::class, 'export'])
        ->permission(Permission::WEBSITE_PROJECT_SETTING_EXPORT());


    Route::put('/{id}', [WebsiteProjectSettingController::class, 'update'])
        ->permission(Permission::WEBSITE_PROJECT_SETTING_UPDATE());
    Route::delete('/{id}', [WebsiteProjectSettingController::class, 'delete'])
        ->permission(Permission::WEBSITE_PROJECT_SETTING_DELETE());
});
