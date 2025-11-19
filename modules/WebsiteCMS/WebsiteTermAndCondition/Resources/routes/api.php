<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Controllers\WebsiteTermAndConditionController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [WebsiteTermAndConditionController::class, 'index']);
    Route::post('/', [WebsiteTermAndConditionController::class, 'store']);
    Route::post('/export', [WebsiteTermAndConditionController::class, 'export']);

    Route::get('/{id}', [WebsiteTermAndConditionController::class, 'show']);
    Route::put('/{id}', [WebsiteTermAndConditionController::class, 'update']);
    Route::delete('/{id}', [WebsiteTermAndConditionController::class, 'delete']);
});
