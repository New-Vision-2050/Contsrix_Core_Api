<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\NatureWork\Controllers\NatureWorkController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [NatureWorkController::class, 'index']);
    Route::post('/', [NatureWorkController::class, 'store']);
    Route::get('/{id}', [NatureWorkController::class, 'show']);
    Route::put('/{id}', [NatureWorkController::class, 'update']);
    Route::delete('/{id}', [NatureWorkController::class, 'delete']);
});
