<?php

use Illuminate\Support\Facades\Route;
use Modules\WebsiteCMS\Founder\Controllers\FounderController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FounderController::class, 'index']);
    Route::post('/', [FounderController::class, 'store']);
    Route::post('/export', [FounderController::class, 'export']);

    Route::get('/{id}', [FounderController::class, 'show']);
    Route::post('/{id}', [FounderController::class, 'update']);
    Route::delete('/{id}', [FounderController::class, 'delete']);
});
