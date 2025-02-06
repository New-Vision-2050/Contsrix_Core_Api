<?php

use Illuminate\Support\Facades\Route;
use Modules\CompanyUser\Controllers\CompanyUserController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyUserController::class, 'index']);
    Route::get('/widgets', [CompanyUserController::class, 'widgets']);
    Route::post('/', [CompanyUserController::class, 'store']);
    Route::post('/validations', [CompanyUserController::class, 'validation']);
    Route::get('/{id}', [CompanyUserController::class, 'show']);
    Route::put('/{id}', [CompanyUserController::class, 'update']);
    Route::post('/{id}/assign-role', [CompanyUserController::class, 'assignRoleForCompanies']);
    Route::delete('/{id}', [CompanyUserController::class, 'delete']);
    Route::delete('/{id}/specific-role', [CompanyUserController::class, 'deleteForSpecificRole']);
});
