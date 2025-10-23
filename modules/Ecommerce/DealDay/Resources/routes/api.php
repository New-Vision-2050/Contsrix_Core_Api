<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\DealDay\Controllers\DealDayController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [DealDayController::class, 'index']);
    Route::post('/', [DealDayController::class, 'store']);
    Route::post('/export', [DealDayController::class, 'export']);
    
    Route::get('/statistics', [DealDayController::class, 'getStatistics']);

    Route::get('/{id}', [DealDayController::class, 'show']);
    Route::put('/{id}', [DealDayController::class, 'update']);
    Route::patch('/{id}/toggle-status', [DealDayController::class, 'toggleStatus']);
    Route::delete('/{id}', [DealDayController::class, 'delete']);
});
