<?php

use Illuminate\Support\Facades\Route;
use Modules\MedicalInsurance\Controllers\MedicalInsuranceController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [MedicalInsuranceController::class, 'index']);
    Route::post('/', [MedicalInsuranceController::class, 'store']);
    Route::post('/export', [MedicalInsuranceController::class, 'export']);

    Route::get('/{id}', [MedicalInsuranceController::class, 'show']);
    Route::put('/{id}', [MedicalInsuranceController::class, 'update']);
    Route::delete('/{id}', [MedicalInsuranceController::class, 'delete']);
});
