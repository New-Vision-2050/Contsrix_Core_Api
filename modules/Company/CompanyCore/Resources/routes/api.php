<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyCore\Controllers\CompanyController;


Route::domain('{subdomain}.' . env('APP_URL'))->group(function () {
    Route::get('/', [CompanyController::class, 'handleSubdomain']);
});


Route::middleware(['auth:api'])->group(function () {
    Route::get('/', [CompanyController::class, 'index']);
    Route::get('/widget', [CompanyController::class, 'widget']);
    Route::post('/', [CompanyController::class, 'store']);
    Route::post('/validate', [CompanyController::class, 'validate']);
    Route::get('/{id}', [CompanyController::class, 'show']);
    Route::put('/{id}', [CompanyController::class, 'update']);
    Route::put('/activate/{id}', [CompanyController::class, 'activate']);
    Route::delete('/{id}', [CompanyController::class, 'delete']);
});
