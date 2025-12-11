<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Controllers\WebsiteHomePageSettingController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/current', [WebsiteHomePageSettingController::class, 'show'])->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
]);

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Get current company's home page setting


    // Update current company's home page setting
    Route::post('/current', [WebsiteHomePageSettingController::class, 'update'])
        ->permission(Permission::WEBSITE_HOME_PAGE_SETTING_UPDATE());
});
