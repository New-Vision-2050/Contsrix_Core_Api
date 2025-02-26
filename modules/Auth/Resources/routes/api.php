<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\AuthController;
Route::group(['middleware' => ['throttle:5,1']],function (){
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-step', [AuthController::class, 'loginBySteps']);
    Route::post('/login-otp', [AuthController::class, 'loginWithOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/get-login-ways', [AuthController::class, 'getLoginWays']);
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/check-answers-questions', [AuthController::class, 'checkAnswers']);

});


Route::group(['middleware' => ['auth:api']], function () {

    Route::post('/logout', [AuthController::class, 'logout']);

});


