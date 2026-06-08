<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared/Process\Controllers\Shared/ProcessController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [Shared/ProcessController::class, 'index']);
    Route::post('/', [Shared/ProcessController::class, 'store']);
    Route::post('/export', [Shared/ProcessController::class, 'export']);

    Route::get('/{id}', [Shared/ProcessController::class, 'show']);
    Route::put('/{id}', [Shared/ProcessController::class, 'update']);
    Route::delete('/{id}', [Shared/ProcessController::class, 'delete']);
});
