<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\WebsiteCMS\WebsiteThemeSetting\Controllers\WebsiteThemeSettingController;

Route::group(['middleware' => ['auth:api']], function () {
    // CRUD routes
    Route::get('/', [WebsiteThemeSettingController::class, 'index'])->permission(Permission::WEBSITE_THEME_SETTING_LIST());
//    Route::post('/', [WebsiteThemeSettingController::class, 'store']);
//    Route::post('/export', [WebsiteThemeSettingController::class, 'export']);
    Route::get('/{id}', [WebsiteThemeSettingController::class, 'show'])->permission(Permission::WEBSITE_THEME_SETTING_SHOW());
//    Route::put('/{id}', [WebsiteThemeSettingController::class, 'update']);
//    Route::delete('/{id}', [WebsiteThemeSettingController::class, 'delete']);

    // Company assignment routes

});
Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    Route::post('/assign-to-company', [WebsiteThemeSettingController::class, 'assignThemeToCompany'])->permission(Permission::WEBSITE_THEME_SETTING_ACTIVATE());
    Route::get('/get-default-theme', [WebsiteThemeSettingController::class, 'getCompanyThemeSetting']);
    Route::get('/default/theme', [WebsiteThemeSettingController::class, 'getDefaultThemeSetting']);

});

