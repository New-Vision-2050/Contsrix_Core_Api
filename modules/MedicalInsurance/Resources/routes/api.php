<?php

use Illuminate\Support\Facades\Route;
use Modules\MedicalInsurance\Controllers\MedicalInsuranceController;
use Modules\MedicalInsurance\Controllers\MedicalInsuranceCategoryController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [MedicalInsuranceController::class, 'index']);
    Route::post('/', [MedicalInsuranceController::class, 'store']);
    Route::post('/export', [MedicalInsuranceController::class, 'export']);

    Route::get('/{id}', [MedicalInsuranceController::class, 'show']);
    Route::put('/{id}', [MedicalInsuranceController::class, 'update']);
    Route::delete('/{id}', [MedicalInsuranceController::class, 'delete']);

    Route::prefix('/{id}/categories')->group(function () {
        Route::get('/', [MedicalInsuranceCategoryController::class, 'index']);
        Route::post('/', [MedicalInsuranceCategoryController::class, 'store']);
        Route::get('/{category_id}', [MedicalInsuranceCategoryController::class, 'show']);
        Route::put('/{category_id}', [MedicalInsuranceCategoryController::class, 'update']);
        Route::delete('/{category_id}', [MedicalInsuranceCategoryController::class, 'delete']);
    });
});
