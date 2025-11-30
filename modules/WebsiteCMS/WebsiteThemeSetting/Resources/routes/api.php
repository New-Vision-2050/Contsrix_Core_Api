<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteThemeSetting\Controllers\WebsiteThemeSettingController;

Route::group(['middleware' => ['auth:api']], function () {
    // CRUD routes
    Route::get('/', [WebsiteThemeSettingController::class, 'index']);
//    Route::post('/', [WebsiteThemeSettingController::class, 'store']);
//    Route::post('/export', [WebsiteThemeSettingController::class, 'export']);
    Route::get('/{id}', [WebsiteThemeSettingController::class, 'show']);
//    Route::put('/{id}', [WebsiteThemeSettingController::class, 'update']);
//    Route::delete('/{id}', [WebsiteThemeSettingController::class, 'delete']);

    // Company assignment routes

});
Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    Route::post('/assign-to-company', [WebsiteThemeSettingController::class, 'assignThemeToCompany']);
    Route::get('/get-default-theme', [WebsiteThemeSettingController::class, 'getCompanyThemeSetting']);
    Route::get('/default/theme', [WebsiteThemeSettingController::class, 'getDefaultThemeSetting']);

});

