<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyCore\Controllers\CompanyController;


Route::middleware(['auth:api'])->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/widget', [CompanyController::class, 'widget']);
    Route::post('/', [CompanyController::class, 'store'])->name('companies.store');
    Route::post('/validate', [CompanyController::class, 'validate']);
    Route::post('/test', [CompanyController::class, 'test']);

    Route::put('/{id}/activate', [CompanyController::class, 'activate']);
    Route::get('/{id}', [CompanyController::class, 'show'])->name('companies.show');
    Route::put('/{id}', [CompanyController::class, 'update']);
    Route::delete('/{id}', [CompanyController::class, 'delete'])->name('companies.delete');
    Route::prefix("{id}/company-profile")->group(function () {
        Route::prefix("official-data")->group(function () {
           Route::put("/",[\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class,"updateOfficialData"]);
           Route::put("/request",[\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class,"updateOfficialDataRequest"]);
        });

        Route::prefix("national-address")->group(function () {
           Route::post("/",[\Modules\Company\CompanyCore\Controllers\CompanyProfileController::class,"getAddressFromMap"]);
        });

    });
});
