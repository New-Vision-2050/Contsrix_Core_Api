<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\MaritalStatus\Controllers\MaritalStatusController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [MaritalStatusController::class, 'index']);
    Route::post('/', [MaritalStatusController::class, 'store']);
    Route::get('/{id}', [MaritalStatusController::class, 'show']);
    Route::put('/{id}', [MaritalStatusController::class, 'update']);
    Route::delete('/{id}', [MaritalStatusController::class, 'delete']);
});
