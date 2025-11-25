<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteOurService\Controllers\WebsiteOurServiceController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Current company routes
    Route::get('/current', [WebsiteOurServiceController::class, 'getCurrentCompany']);
    Route::post('/current', [WebsiteOurServiceController::class, 'updateCurrentCompany']);

    // Standard CRUD routes
    Route::get('/', [WebsiteOurServiceController::class, 'index']);
});
