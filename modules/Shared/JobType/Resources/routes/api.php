<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\JobType\Controllers\JobTypeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [JobTypeController::class, 'index']);
    Route::get('/list', [JobTypeController::class, 'listSimple']);
    Route::post('/', [JobTypeController::class, 'store']);
    Route::get('/{id}', [JobTypeController::class, 'show']);
    Route::put('/{id}', [JobTypeController::class, 'update']);
    Route::delete('/{id}', [JobTypeController::class, 'delete']);
});
