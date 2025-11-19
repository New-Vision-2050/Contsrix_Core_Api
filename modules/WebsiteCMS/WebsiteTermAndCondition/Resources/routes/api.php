<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Controllers\WebsiteTermAndConditionController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteTermAndConditionController::class, 'index']);
    Route::get('/current', [WebsiteTermAndConditionController::class, 'getForCurrentCompany']);
    Route::put('/current', [WebsiteTermAndConditionController::class, 'updateForCurrentCompany']);
    Route::post('/export', [WebsiteTermAndConditionController::class, 'export']);

    Route::get('/{id}', [WebsiteTermAndConditionController::class, 'show']);
    Route::put('/{id}', [WebsiteTermAndConditionController::class, 'update']);
    Route::delete('/{id}', [WebsiteTermAndConditionController::class, 'delete']);
});
