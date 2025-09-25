<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoInstallment\Controllers\EcoInstallmentController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoInstallmentController::class, 'index']);
    Route::post('/', [EcoInstallmentController::class, 'upsert']);
    Route::post('/export', [EcoInstallmentController::class, 'export']);

    Route::get('/{id}', [EcoInstallmentController::class, 'show']);
    Route::delete('/{id}', [EcoInstallmentController::class, 'delete']);
});
