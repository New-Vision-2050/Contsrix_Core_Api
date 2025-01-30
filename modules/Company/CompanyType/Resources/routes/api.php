<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyType\Controllers\CompanyTypeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyTypeController::class, 'index']);
    Route::post('/', [CompanyTypeController::class, 'store']);
    Route::get('/{id}', [CompanyTypeController::class, 'show']);
    Route::put('/{id}', [CompanyTypeController::class, 'update']);
    Route::delete('/{id}', [CompanyTypeController::class, 'delete']);
});
