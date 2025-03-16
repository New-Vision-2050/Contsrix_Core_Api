<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyCore\Controllers\CompanyController;


Route::middleware(['auth:api'])->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/widget', [CompanyController::class, 'widget']);
    Route::post('/', [CompanyController::class, 'store'])->name('companies.store');
    Route::post('/validated', [CompanyController::class, 'validated']);
    Route::post('/test', [CompanyController::class, 'test']);

    Route::get('/{id}', [CompanyController::class, 'show'])->name('companies.show');
    Route::put('/{id}', [CompanyController::class, 'update']);
    Route::put('/activate/{id}', [CompanyController::class, 'activate']);
    Route::delete('/{id}', [CompanyController::class, 'delete'])->name('companies.delete');
});
