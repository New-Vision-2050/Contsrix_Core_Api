<?php

use Illuminate\Support\Facades\Route;
use Modules\JobTitle\Controllers\JobTitleController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [JobTitleController::class, 'index']);
    Route::get('/list', [JobTitleController::class, 'listSimple']);
    Route::post('/', [JobTitleController::class, 'store']);
    Route::get('/{id}', [JobTitleController::class, 'show']);
    Route::put('/{id}', [JobTitleController::class, 'update']);
    Route::delete('/{id}', [JobTitleController::class, 'delete']);
    Route::patch('/{id}/status', [JobTitleController::class, 'changeStatus']);
});
