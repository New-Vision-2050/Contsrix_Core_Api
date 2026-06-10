<?php

use Illuminate\Support\Facades\Route;
use Modules\Process\Controllers\ProcessController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ProcessController::class, 'index']);
    Route::post('/', [ProcessController::class, 'store']);
    Route::post('/export', [ProcessController::class, 'export']);

    Route::get('/{id}', [ProcessController::class, 'show']);
    Route::put('/{id}', [ProcessController::class, 'update']);
    Route::delete('/{id}', [ProcessController::class, 'delete']);
});
