<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\AuthController;
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-otp', [AuthController::class, 'loginWithOtp']);

Route::group(['middleware' => ['auth:api']], function () {

    Route::post('/logout', [AuthController::class, 'logout']);

});
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
