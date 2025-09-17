<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Payment\Controllers\PaymentController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::post('/export', [PaymentController::class, 'export']);

    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::put('/{id}', [PaymentController::class, 'update']);
    Route::delete('/{id}', [PaymentController::class, 'delete']);
});
