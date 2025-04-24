<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\TypeAllowance\Controllers\TypeAllowanceController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [TypeAllowanceController::class, 'index']);
    Route::post('/', [TypeAllowanceController::class, 'store']);
    Route::get('/{id}', [TypeAllowanceController::class, 'show']);
    Route::put('/{id}', [TypeAllowanceController::class, 'update']);
    Route::delete('/{id}', [TypeAllowanceController::class, 'delete']);
});
