<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteContactInfo\Controllers\WebsiteContactInfoController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    // Current company contact info endpoints
    Route::get('/current', [WebsiteContactInfoController::class, 'getCurrentCompanyContactInfo']);
    Route::put('/current', [WebsiteContactInfoController::class, 'updateCurrentCompanyContactInfo']);

    // Standard CRUD endpoints
    Route::get('/', [WebsiteContactInfoController::class, 'index']);
    Route::post('/export', [WebsiteContactInfoController::class, 'export']);

    Route::get('/{id}', [WebsiteContactInfoController::class, 'show']);
});
