<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyRegistrationType\Controllers\CompanyRegistrationTypeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyRegistrationTypeController::class, 'index']);
    Route::post('/', [CompanyRegistrationTypeController::class, 'store']);
    Route::get('/{id}', [CompanyRegistrationTypeController::class, 'show']);
    Route::put('/{id}', [CompanyRegistrationTypeController::class, 'update']);
    Route::delete('/{id}', [CompanyRegistrationTypeController::class, 'delete']);
});
