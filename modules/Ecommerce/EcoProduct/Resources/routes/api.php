<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoProduct\Controllers\EcoProductController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [EcoProductController::class, 'index']);
    Route::post('/', [EcoProductController::class, 'store']);
    Route::get('/{id}', [EcoProductController::class, 'show']);
    Route::put('/{id}', [EcoProductController::class, 'update']);
    Route::delete('/{id}', [EcoProductController::class, 'delete']);
});
