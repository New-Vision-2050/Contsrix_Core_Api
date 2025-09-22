<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoAddress\Controllers\Customer\EcoAddressCustomerController;

/*
|--------------------------------------------------------------------------
| Customer API Routes - EcoAddress
|--------------------------------------------------------------------------
|
| Customer-facing routes for address management
| Requires customer authentication via Sanctum
|
*/

Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'customer/addresses'], function () {
    
    Route::get('/', [EcoAddressCustomerController::class, 'index']);
    Route::post('/', [EcoAddressCustomerController::class, 'store']);
    Route::put('/{id}', [EcoAddressCustomerController::class, 'update']);
    Route::delete('/{id}', [EcoAddressCustomerController::class, 'destroy']);
    Route::patch('/{id}/set-default', [EcoAddressCustomerController::class, 'setDefault']);
    Route::get('/{id}', [EcoAddressCustomerController::class, 'show']);
    Route::get('/default/{type?}', [EcoAddressCustomerController::class, 'getDefault']);
});

