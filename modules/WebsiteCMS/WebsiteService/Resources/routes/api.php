<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteService\Controllers\WebsiteServiceController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteServiceController::class, 'index']);
    Route::post('/', [WebsiteServiceController::class, 'store']);
    Route::get('/{id}', [WebsiteServiceController::class, 'show']);
    Route::put('/{id}', [WebsiteServiceController::class, 'update']);
    Route::delete('/{id}', [WebsiteServiceController::class, 'destroy']);
    Route::post('/export', [WebsiteServiceController::class, 'export']);
});
