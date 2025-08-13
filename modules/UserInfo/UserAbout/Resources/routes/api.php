<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserAbout\Controllers\UserAboutController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserAboutController::class, 'index']);
    Route::post('/', [UserAboutController::class, 'store']);
});
