<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\TimeZone\Controllers\TimeZoneController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [TimeZoneController::class, 'index']);
    Route::post('/', [TimeZoneController::class, 'store']);
    Route::get('/{id}', [TimeZoneController::class, 'show']);
    Route::put('/{id}', [TimeZoneController::class, 'update']);
    Route::delete('/{id}', [TimeZoneController::class, 'delete']);
});
