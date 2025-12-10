<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\WebsiteCMS\WebsiteTheme\Controllers\WebsiteThemeController;

Route::group(['middleware' => [ \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/current-company', [WebsiteThemeController::class, 'getCurrentCompanyTheme']);
    Route::get('/current-company-with-attributes', [WebsiteThemeController::class, 'getCurrentCompanyThemeWithAttributes']);
});

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteThemeController::class, 'index']);
//    Route::post('/', [WebsiteThemeController::class, 'store']);
//    Route::post('/export', [WebsiteThemeController::class, 'export']);

    // Current company theme routes

    Route::post('/current-company', [WebsiteThemeController::class, 'updateCurrentCompanyTheme'])
    ->permission(Permission::WEBSITE_THEME_UPDATE());

//    Route::get('/{id}', [WebsiteThemeController::class, 'show']);
    Route::put('/{id}', [WebsiteThemeController::class, 'update']);
//    Route::delete('/{id}', [WebsiteThemeController::class, 'delete']);
});
