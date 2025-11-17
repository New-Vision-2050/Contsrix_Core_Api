<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Controllers\CategoryWebsiteCMSController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [CategoryWebsiteCMSController::class, 'index']);
    Route::get('/categeory-types', [CategoryWebsiteCMSController::class, 'getCetegoryTypes']);
    Route::post('/', [CategoryWebsiteCMSController::class, 'store']);
    Route::post('/export', [CategoryWebsiteCMSController::class, 'export']);

    Route::get('/{id}', [CategoryWebsiteCMSController::class, 'show']);
    Route::put('/{id}', [CategoryWebsiteCMSController::class, 'update']);
    Route::delete('/{id}', [CategoryWebsiteCMSController::class, 'delete']);
});
