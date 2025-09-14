<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\OrderTransaction\Controllers\OrderTransactionController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [OrderTransactionController::class, 'index']);
    Route::post('/', [OrderTransactionController::class, 'store']);
    Route::post('/export', [OrderTransactionController::class, 'export']);

    Route::get('/{id}', [OrderTransactionController::class, 'show']);
    Route::put('/{id}', [OrderTransactionController::class, 'update']);
    Route::delete('/{id}', [OrderTransactionController::class, 'delete']);
});
