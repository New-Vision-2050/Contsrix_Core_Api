<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\BusinessType\Controllers\BusinessTypeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [BusinessTypeController::class, 'index']);
    Route::post('/', [BusinessTypeController::class, 'store']);
    Route::get('/{id}', [BusinessTypeController::class, 'show']);
    Route::put('/{id}', [BusinessTypeController::class, 'update']);
    Route::delete('/{id}', [BusinessTypeController::class, 'delete']);
});
