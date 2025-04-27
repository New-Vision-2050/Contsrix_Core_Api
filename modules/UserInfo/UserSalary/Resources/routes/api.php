<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserSalary\Controllers\UserSalaryController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [UserSalaryController::class, 'index']);
    Route::post('/', [UserSalaryController::class, 'store']);
    Route::get('/{id}', [UserSalaryController::class, 'show']);
    Route::put('/{id}', [UserSalaryController::class, 'update']);
    Route::delete('/', [UserSalaryController::class, 'delete']);
});
