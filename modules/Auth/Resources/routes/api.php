<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\AuthController;
use Stancl\Tenancy\Features\UserImpersonation;

Route::group(['middleware' => ['throttle:35,1',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]],function (){
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-as-admin', [AuthController::class, 'loginAsAdmin']);

    Route::post('/login-step', [AuthController::class, 'loginBySteps']);
    Route::post('/login-otp', [AuthController::class, 'loginWithOtp']);
    Route::post('/validate-reset-password-otp', [AuthController::class, 'validateOtp']);
    Route::post('/alternative-step-login', [AuthController::class, 'loginStepAlternative']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/get-login-ways', [AuthController::class, 'getLoginWays']);
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/check-answers-questions', [AuthController::class, 'checkAnswers']);
    Route::post('/change-email', [AuthController::class, 'changeEmail']);

});


Route::group(['middleware' => ['auth:api']
], function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::get('/get-data-for-login-as-admin', [AuthController::class, 'getDataForLoginAsAdmin']);


});


