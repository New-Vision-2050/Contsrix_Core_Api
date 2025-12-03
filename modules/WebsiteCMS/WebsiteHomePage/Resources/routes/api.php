<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteHomePage\Controllers\WebsiteHomePageController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Get home page data (HomePageSetting, OurServices, Featured Projects)
    Route::get('/data', [WebsiteHomePageController::class, 'getHomePageData']);
    
    Route::get('/', [WebsiteHomePageController::class, 'index']);
    Route::post('/', [WebsiteHomePageController::class, 'store']);
    Route::post('/export', [WebsiteHomePageController::class, 'export']);

    Route::get('/{id}', [WebsiteHomePageController::class, 'show']);
    Route::put('/{id}', [WebsiteHomePageController::class, 'update']);
    Route::delete('/{id}', [WebsiteHomePageController::class, 'delete']);
});
