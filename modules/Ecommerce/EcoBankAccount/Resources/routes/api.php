<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoBankAccount\Controllers\EcoBankAccountController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [EcoBankAccountController::class, 'index']);
    Route::post('/', [EcoBankAccountController::class, 'store']);
    Route::post('/export', [EcoBankAccountController::class, 'export']);

    Route::get('/{id}', [EcoBankAccountController::class, 'show']);
    Route::put('/{id}', [EcoBankAccountController::class, 'update']);
    Route::delete('/{id}', [EcoBankAccountController::class, 'delete']);
});
