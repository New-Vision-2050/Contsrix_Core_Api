<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoShopAddress\Controllers\EcoShopAddressController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [EcoShopAddressController::class, 'show']);
    Route::post('/', [EcoShopAddressController::class, 'store']);
    Route::post('/export', [EcoShopAddressController::class, 'export']);
});
