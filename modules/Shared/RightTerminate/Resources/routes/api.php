<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\RightTerminate\Controllers\RightTerminateController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [RightTerminateController::class, 'index']);
    Route::post('/', [RightTerminateController::class, 'store']);
    Route::get('/{id}', [RightTerminateController::class, 'show']);
    Route::put('/{id}', [RightTerminateController::class, 'update']);
    Route::delete('/{id}', [RightTerminateController::class, 'delete']);
});
