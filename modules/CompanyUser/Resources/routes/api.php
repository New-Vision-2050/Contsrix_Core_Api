<?php

use Illuminate\Support\Facades\Route;
use Modules\CompanyUser\Controllers\CompanyUserController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [CompanyUserController::class, 'index']);
    Route::post('/', [CompanyUserController::class, 'store']);
    Route::get('/{id}', [CompanyUserController::class, 'show']);
    Route::put('/{id}', [CompanyUserController::class, 'update']);
    Route::delete('/{id}', [CompanyUserController::class, 'delete']);
});
