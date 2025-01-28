<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\AuthController;
Route::group(['middleware' => ['throttle:5,1']],function (){
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-otp', [AuthController::class, 'loginWithOtp'])->middleware(\Modules\Auth\Middleware\ContinueWithOtp::class);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});


Route::group(['middleware' => ['auth:api']], function () {

    Route::post('/logout', [AuthController::class, 'logout']);

});
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);

