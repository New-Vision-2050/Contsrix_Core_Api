<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\Controllers\CompanyController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyController::class, 'index']);
    Route::post('/', [CompanyController::class, 'store']);
    Route::get('/{id}', [CompanyController::class, 'show']);
    Route::put('/{id}', [CompanyController::class, 'update']);
    Route::delete('/{id}', [CompanyController::class, 'delete']);
});
