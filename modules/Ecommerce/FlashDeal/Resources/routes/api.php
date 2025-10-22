<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\FlashDeal\Controllers\FlashDealController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [FlashDealController::class, 'index']);
    Route::post('/', [FlashDealController::class, 'store']);
    Route::post('/export', [FlashDealController::class, 'export']);

    Route::get('/{id}', [FlashDealController::class, 'show']);
    Route::post('/{id}', [FlashDealController::class, 'update']);
    Route::patch('/{id}/toggle-status', [FlashDealController::class, 'toggleStatus']);
    Route::delete('/{id}', [FlashDealController::class, 'delete']);
});
