<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\AuthController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [AuthController::class, 'index']);
    Route::post('/', [AuthController::class, 'store']);
    Route::get('/{id}', [AuthController::class, 'show']);
    Route::put('/{id}', [AuthController::class, 'update']);
    Route::delete('/{id}', [AuthController::class, 'delete']);
});
