<?php

use Illuminate\Support\Facades\Route;
use Modules\CompanyUser\Controllers\CompanyUserController;
use Modules\CompanyUser\Controllers\CompanyUserProfileController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [CompanyUserController::class, 'index']);
    Route::get('/widgets', [CompanyUserController::class, 'widgets']);
    Route::get('/roles', [CompanyUserController::class, 'roles']);
    Route::get('/profile', [CompanyUserProfileController::class, 'profile']);

    Route::get('/show-by-email/{email}', [CompanyUserController::class, 'showByEmail']);
    Route::post('/change-time-zone/{id}', [CompanyUserController::class, 'changeTimeZone']);
    Route::post('/', [CompanyUserController::class, 'store']);
    Route::post('/validations', [CompanyUserController::class, 'validation']);
    Route::get('/{id}', [CompanyUserController::class, 'show']);
    Route::put('/{id}', [CompanyUserController::class, 'update']);
    Route::post('/{id}/assign-role', [CompanyUserController::class, 'assignRoleForCompanies']);
    Route::delete('/{id}', [CompanyUserController::class, 'delete']);
    Route::delete('/{id}/specific-role', [CompanyUserController::class, 'deleteForSpecificRole']);
});
