<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Warehous\Controllers\WarehousController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [WarehousController::class, 'index']);
    Route::post('/', action: [WarehousController::class, 'store']);
    Route::post('/export', [WarehousController::class, 'export']);
    Route::get('/statistics', [WarehousController::class, 'getStatistics']);
    Route::get('/{id}', [WarehousController::class, 'show']);
    Route::put('/{id}', [WarehousController::class, 'update']);
    Route::delete('/{id}', [WarehousController::class, 'delete']);
});
