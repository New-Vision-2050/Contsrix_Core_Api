<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteTheme\Controllers\WebsiteThemeController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteThemeController::class, 'index']);
    Route::post('/', [WebsiteThemeController::class, 'store']);
    Route::post('/export', [WebsiteThemeController::class, 'export']);

    // Current company theme routes
    Route::get('/current-company', [WebsiteThemeController::class, 'getCurrentCompanyTheme']);
    Route::post('/current-company', [WebsiteThemeController::class, 'updateCurrentCompanyTheme']);

    Route::get('/{id}', [WebsiteThemeController::class, 'show']);
    Route::put('/{id}', [WebsiteThemeController::class, 'update']);
    Route::delete('/{id}', [WebsiteThemeController::class, 'delete']);
});
