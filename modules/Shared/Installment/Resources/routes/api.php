<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Installment\Controllers\InstallmentController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [InstallmentController::class, 'index']);
    Route::post('/', [InstallmentController::class, 'store']);
    Route::post('/export', [InstallmentController::class, 'export']);

    Route::get('/{id}', [InstallmentController::class, 'show']);
    Route::put('/{id}', [InstallmentController::class, 'update']);
    Route::delete('/{id}', [InstallmentController::class, 'delete']);
});
