<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoBrand\Controllers\EcoBrandController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(callback: function () {
    Route::get('/', [EcoBrandController::class, 'index']);
    Route::post('/', [EcoBrandController::class, 'store']);
    Route::get('/{id}', [EcoBrandController::class, 'show']);
    Route::put('/{id}', [EcoBrandController::class, 'update']);
    Route::delete('/{id}', [EcoBrandController::class, 'delete']);
});
