<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoAddress\Controllers\EcoAddressController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoAddressController::class, 'index']);
    Route::post('/', [EcoAddressController::class, 'store']);
    Route::post('/export', [EcoAddressController::class, 'export']);

    Route::get('/{id}', [EcoAddressController::class, 'show']);
    Route::put('/{id}', [EcoAddressController::class, 'update']);
    Route::delete('/{id}', [EcoAddressController::class, 'delete']);
});
