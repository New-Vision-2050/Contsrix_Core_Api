<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoProduct\Controllers\EcoProductController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [EcoProductController::class, 'index']);
    Route::post('/', [EcoProductController::class, 'store']);
    Route::get('/{id}', [EcoProductController::class, 'show']);
    Route::post('/{id}', [EcoProductController::class, 'update']);
    Route::delete('/{id}', [EcoProductController::class, 'delete']);
});
