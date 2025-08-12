<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\Biography\Controllers\BiographyController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [BiographyController::class, 'index']);
    Route::post('/', [BiographyController::class, 'store']);
    Route::get('/{id}', [BiographyController::class, 'show']);
    Route::put('/{id}', [BiographyController::class, 'update']);
    Route::delete('/{id}', [BiographyController::class, 'delete']);
});
