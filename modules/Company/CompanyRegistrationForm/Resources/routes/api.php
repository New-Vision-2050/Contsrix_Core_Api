<?php

use Illuminate\Support\Facades\Route;
use Modules\Company\CompanyRegistrationForm\Controllers\CompanyRegistrationFormController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyRegistrationFormController::class, 'index']);
    Route::post('/', [CompanyRegistrationFormController::class, 'store']);
    Route::get('/{id}', [CompanyRegistrationFormController::class, 'show']);
    Route::put('/{id}', [CompanyRegistrationFormController::class, 'update']);
    Route::delete('/{id}', [CompanyRegistrationFormController::class, 'delete']);
});
