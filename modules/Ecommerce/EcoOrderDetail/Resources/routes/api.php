<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoOrderDetail\Controllers\EcoOrderDetailController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [EcoOrderDetailController::class, 'index']);
    Route::post('/', [EcoOrderDetailController::class, 'store']);
    Route::post('/export', [EcoOrderDetailController::class, 'export']);

    Route::get('/{id}', [EcoOrderDetailController::class, 'show']);
    Route::put('/{id}', [EcoOrderDetailController::class, 'update']);
    Route::delete('/{id}', [EcoOrderDetailController::class, 'delete']);
});
