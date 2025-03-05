<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminRequest\Controllers\AdminRequestController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [AdminRequestController::class, 'index']);
    Route::post('/', [AdminRequestController::class, 'store']);
    Route::get('/{id}', [AdminRequestController::class, 'show']);
    Route::put('/{id}', [AdminRequestController::class, 'update']);
    Route::delete('/{id}', [AdminRequestController::class, 'delete']);
});
