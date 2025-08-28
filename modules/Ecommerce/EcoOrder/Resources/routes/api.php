<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoOrder\Controllers\EcoOrderController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [EcoOrderController::class, 'index']);
    Route::post('/', [EcoOrderController::class, 'store']);
    Route::post('/export', [EcoOrderController::class, 'export']);

    Route::get('/{id}', [EcoOrderController::class, 'show']);
    Route::put('/{id}', [EcoOrderController::class, 'update']);
    Route::delete('/{id}', [EcoOrderController::class, 'delete']);
});
