<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoBankAccount\Controllers\Dashboard\EcoBankAccountDashboardController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], 'prefix' => 'dashboard/bank-accounts'], function () {

    Route::get('/', [EcoBankAccountDashboardController::class, 'index']);
    Route::post('/', [EcoBankAccountDashboardController::class, 'store']);
    Route::post('/export', [EcoBankAccountDashboardController::class, 'export']);

    Route::get('/{id}', [EcoBankAccountDashboardController::class, 'show']);
    Route::put('/{id}', [EcoBankAccountDashboardController::class, 'update']);
    Route::delete('/{id}', [EcoBankAccountDashboardController::class, 'delete']);
});
