<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoBrand\Controllers\EcoBrandController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoBrandController::class, 'index']);
    Route::post('/', [EcoBrandController::class, 'store']);
    Route::get('/{id}', [EcoBrandController::class, 'show']);
    Route::put('/{id}', [EcoBrandController::class, 'update']);
    Route::delete('/{id}', [EcoBrandController::class, 'delete']);
});
