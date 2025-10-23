<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\FeatureDeal\Controllers\FeatureDealController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FeatureDealController::class, 'index']);
    Route::post('/', [FeatureDealController::class, 'store']);
    Route::post('/export', [FeatureDealController::class, 'export']);
    
    Route::get('/statistics', [FeatureDealController::class, 'getStatistics']);

    Route::get('/{id}', [FeatureDealController::class, 'show']);
    Route::put('/{id}', [FeatureDealController::class, 'update']);
    Route::patch('/{id}/toggle-status', [FeatureDealController::class, 'toggleStatus']);
    Route::delete('/{id}', [FeatureDealController::class, 'delete']);
});
