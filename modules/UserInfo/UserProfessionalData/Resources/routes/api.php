<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserProfessionalData\Controllers\UserProfessionalDataController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('user/{id}', [UserProfessionalDataController::class, 'index']);
    Route::post('/', [UserProfessionalDataController::class, 'store']);
    Route::get('/{id}', [UserProfessionalDataController::class, 'show']);
    Route::put('/{id}', [UserProfessionalDataController::class, 'update']);
    Route::delete('/{id}', [UserProfessionalDataController::class, 'delete']);
});
