<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Currency\Controllers\CurrencyController;
use Illuminate\Support\Facades\Artisan;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CurrencyController::class, 'index']);
    Route::post('/', [CurrencyController::class, 'store']);
    Route::get('/{id}', [CurrencyController::class, 'show']);
    Route::put('/{id}', [CurrencyController::class, 'update']);
    Route::delete('/{id}', [CurrencyController::class, 'delete']);
});
