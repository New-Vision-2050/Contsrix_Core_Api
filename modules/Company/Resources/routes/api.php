<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\Controllers\CompanyController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyController::class, 'index']);
    Route::get('/widget', [CompanyController::class, 'widget']);
    Route::post('/', [CompanyController::class, 'store']);
    Route::post('/validate', [CompanyController::class, 'validate']);
    Route::get('/{id}', [CompanyController::class, 'show']);
    Route::put('/{id}', [CompanyController::class, 'update']);
    Route::put('/activate/{id}', [CompanyController::class, 'activate']);
    Route::delete('/{id}', [CompanyController::class, 'delete']);
});
