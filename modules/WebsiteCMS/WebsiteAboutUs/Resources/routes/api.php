<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteAboutUs\Controllers\WebsiteAboutUsController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Current company routes
    Route::get('/current', [WebsiteAboutUsController::class, 'getCurrentCompanyAboutUs']);
    Route::post('/current', [WebsiteAboutUsController::class, 'updateCurrentCompanyAboutUs']);

    // Standard CRUD routes
    Route::get('/', [WebsiteAboutUsController::class, 'index']);

});
