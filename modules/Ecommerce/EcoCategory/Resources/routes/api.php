<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoCategory\Controllers\EcoCategoryController;


Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoCategoryController::class, 'index']);
    Route::post('/', [EcoCategoryController::class, 'store']);
    
    Route::get('/statistics', [EcoCategoryController::class, 'getStatistics']);
    
    Route::get('/{id}', [EcoCategoryController::class, 'show']);
    Route::put('/{id}', [EcoCategoryController::class, 'update']);
    Route::delete('/{id}', [EcoCategoryController::class, 'delete']);
});
