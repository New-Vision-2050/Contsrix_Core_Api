<?php

use Illuminate\Support\Facades\Route;
use Modules\ActivityLog\Controllers\ActivityLogController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ActivityLogController::class, 'index']);
    Route::post('/', [ActivityLogController::class, 'store']);
    Route::get('/{id}', [ActivityLogController::class, 'show']);
    Route::put('/{id}', [ActivityLogController::class, 'update']);
    Route::delete('/{id}', [ActivityLogController::class, 'delete']);
});
