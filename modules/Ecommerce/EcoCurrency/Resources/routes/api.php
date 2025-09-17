<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoCurrency\Controllers\EcoCurrencyController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(callback: function () {
    Route::get('/', [EcoCurrencyController::class, 'index']);
    Route::post('/', [EcoCurrencyController::class, 'upsert']);
    Route::post('/export', [EcoCurrencyController::class, 'export']);

    Route::get('/{id}', [EcoCurrencyController::class, 'show']);
    Route::delete('/{id}', [EcoCurrencyController::class, 'delete']);
});
