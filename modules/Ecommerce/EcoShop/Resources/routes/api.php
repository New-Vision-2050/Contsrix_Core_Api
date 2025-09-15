<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoShop\Controllers\EcoShopController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {

    Route::get('/', [EcoShopController::class, 'show']);
    Route::post('/', [EcoShopController::class, 'store']);
    Route::post('/upsert', [EcoShopController::class, 'upsert']);
    Route::post('/export', [EcoShopController::class, 'export']);

    Route::put('/{id}', [EcoShopController::class, 'update']);
    Route::delete('/{id}', [EcoShopController::class, 'delete']);
});
