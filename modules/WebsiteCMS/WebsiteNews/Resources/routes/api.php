<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\WebsiteNews\Controllers\WebsiteNewsController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WebsiteNewsController::class, 'index']);
    Route::post('/', [WebsiteNewsController::class, 'store']);
    Route::post('/export', [WebsiteNewsController::class, 'export']);

    Route::get('/{id}', [WebsiteNewsController::class, 'show']);
    Route::post('/{id}', [WebsiteNewsController::class, 'update']);
    Route::delete('/{id}', [WebsiteNewsController::class, 'delete']);
});
