<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoPayment\Controllers\EcoPaymentController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(callback: function () {
    Route::get('/', [EcoPaymentController::class, 'index']);
    Route::post('/', [EcoPaymentController::class, 'upsert']);
    Route::post('/export', [EcoPaymentController::class, 'export']);

    Route::get('/{id}', [EcoPaymentController::class, 'show']);
    Route::delete('/{id}', [EcoPaymentController::class, 'delete']);
});
