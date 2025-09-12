<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoDiscount\Controllers\EcoDiscountController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [EcoDiscountController::class, 'index']);
    Route::post('/', [EcoDiscountController::class, 'store']);

    Route::get('/statistics', [EcoDiscountController::class, 'getStatistics']);
    Route::post('/apply', [EcoDiscountController::class, 'applyDiscount']);
    Route::post('/export', [EcoDiscountController::class, 'export']);
    
    // Product discount management
    Route::put('/product/{id}', [EcoDiscountController::class, 'storeDiscountProduct']);

    Route::get('/{id}', [EcoDiscountController::class, 'show']);
    Route::put('/{id}', [EcoDiscountController::class, 'update']);
    Route::delete('/{id}', [EcoDiscountController::class, 'delete']);
});
