<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserStatus\Controllers\UserStatusController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [UserStatusController::class, 'index']);
    Route::post('/activation/{id}', [UserStatusController::class, 'updateStatus']);
    Route::post('/password/{id}', [UserStatusController::class, 'updatePassword']);
    // Route::post('/activation/{id}', [UserStatusController::class, 'updateStatus']);
    // Route::delete('/{id}', [UserStatusController::class, 'delete']);
});
