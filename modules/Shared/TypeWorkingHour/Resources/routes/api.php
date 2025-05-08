<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\TypeWorkingHour\Controllers\TypeWorkingHourController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [TypeWorkingHourController::class, 'index']);
    Route::post('/', [TypeWorkingHourController::class, 'store']);
    Route::get('/{id}', [TypeWorkingHourController::class, 'show']);
    Route::put('/{id}', [TypeWorkingHourController::class, 'update']);
    Route::delete('/{id}', [TypeWorkingHourController::class, 'delete']);
});
