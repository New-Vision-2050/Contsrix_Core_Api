<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\TimeUnit\Controllers\TimeUnitController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [TimeUnitController::class, 'index']);
    Route::post('/', [TimeUnitController::class, 'store']);
    Route::get('/{id}', [TimeUnitController::class, 'show']);
    Route::put('/{id}', [TimeUnitController::class, 'update']);
    Route::delete('/{id}', [TimeUnitController::class, 'delete']);
});
