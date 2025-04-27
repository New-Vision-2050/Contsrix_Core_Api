<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\SalaryType\Controllers\SalaryTypeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [SalaryTypeController::class, 'index']);
    Route::post('/', [SalaryTypeController::class, 'store']);
    Route::get('/{id}', [SalaryTypeController::class, 'show']);
    Route::put('/{id}', [SalaryTypeController::class, 'update']);
    Route::delete('/{id}', [SalaryTypeController::class, 'delete']);
});
