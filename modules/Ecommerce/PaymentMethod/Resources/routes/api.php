<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\PaymentMethod\Controllers\PaymentMethodController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PaymentMethodController::class, 'index']);
    Route::post('/', [PaymentMethodController::class, 'store']);
    Route::post('/export', [PaymentMethodController::class, 'export']);

    Route::get('/{id}', [PaymentMethodController::class, 'show']);
    Route::put('/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('/{id}', [PaymentMethodController::class, 'delete']);
});
