<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectType\Controllers\ProjectTypeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ProjectTypeController::class, 'index']);
    Route::post('/', [ProjectTypeController::class, 'store']);
    Route::post('/export', [ProjectTypeController::class, 'export']);

    Route::get('/{id}', [ProjectTypeController::class, 'show']);
    Route::put('/{id}', [ProjectTypeController::class, 'update']);
    Route::delete('/{id}', [ProjectTypeController::class, 'delete']);
});
