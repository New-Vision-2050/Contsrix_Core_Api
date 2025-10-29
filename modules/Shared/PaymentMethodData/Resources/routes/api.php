<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\PaymentMethodData\Controllers\PaymentMethodDataController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PaymentMethodDataController::class, 'index']);
    Route::post('/', [PaymentMethodDataController::class, 'store']);
    Route::post('/export', [PaymentMethodDataController::class, 'export']);

    Route::get('/{id}', [PaymentMethodDataController::class, 'show']);
    Route::put('/{id}', [PaymentMethodDataController::class, 'update']);
    Route::delete('/{id}', [PaymentMethodDataController::class, 'delete']);
});
