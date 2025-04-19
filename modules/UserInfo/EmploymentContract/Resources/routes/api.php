<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\EmploymentContract\Controllers\EmploymentContractController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [EmploymentContractController::class, 'index']);
    Route::post('/', [EmploymentContractController::class, 'store']);
    Route::get('/{id}', [EmploymentContractController::class, 'show']);
    Route::put('/{id}', [EmploymentContractController::class, 'update']);
    Route::delete('/{id}', [EmploymentContractController::class, 'delete']);
});
