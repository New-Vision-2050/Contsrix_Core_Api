<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteIcon\Controllers\WebsiteIconController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteIconController::class, 'index']);
    Route::post('/', [WebsiteIconController::class, 'store']);
    Route::post('/export', [WebsiteIconController::class, 'export']);

    Route::get('/{id}', [WebsiteIconController::class, 'show']);
    Route::put('/{id}', [WebsiteIconController::class, 'update']);
    Route::delete('/{id}', [WebsiteIconController::class, 'delete']);
});
