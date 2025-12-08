<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteAboutUs\Controllers\WebsiteAboutUsController;
use Modules\RoleAndPermission\Enums\Permission;

Route::get('/current', [WebsiteAboutUsController::class, 'getCurrentCompanyAboutUs'])->middleware([\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]);


Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Current company routes

    Route::post('/current', [WebsiteAboutUsController::class, 'updateCurrentCompanyAboutUs'])
        ->permission(Permission::WEBSITE_ABOUT_US_UPDATE());

    // Standard CRUD routes
//    Route::get('/', [WebsiteAboutUsController::class, 'index'])
//        ->permission(Permission::WEBSITE_ABOUT_US_LIST());

});
