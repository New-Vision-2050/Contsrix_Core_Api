<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoClient\Controllers\EcoClientController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(callback: function () {
    Route::get('/', [EcoClientController::class, 'index']);
    Route::post('/', [EcoClientController::class, 'store']);
    Route::post('/export', [EcoClientController::class, 'export']);
    Route::get('/statistics', [EcoClientController::class, 'getStatistics']);

    Route::get('/{id}', [EcoClientController::class, 'show']);
    Route::post('/{id}', [EcoClientController::class, 'update']);
    Route::delete('/{id}', [EcoClientController::class, 'delete']);
});
