<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Controllers\SettingController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::post('/', [SettingController::class, 'store']);
    Route::delete('/', [SettingController::class, 'delete']);

    Route::group(["prefix"=>"login-way"], function () {
        Route::get('/', [\Modules\Setting\Controllers\LoginWayController::class, 'index']);
        Route::get('/login-options', [\Modules\Setting\Controllers\LoginWayController::class, 'loginOptions']);
        Route::get('/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'show']);
        Route::post('/', [\Modules\Setting\Controllers\LoginWayController::class, 'store']);
        Route::post('/make-default/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'makeLoginWayDefault']);
        Route::put('/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'update']);
        Route::delete('/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'delete']);
    });

    Route::group(["prefix"=>"identifier"], function () {

        Route::get('/', [\Modules\Setting\Controllers\IdentifierSettingController::class, 'index']);
        Route::post('/make-default/{id}', [\Modules\Setting\Controllers\IdentifierSettingController::class, 'makeDefault']);

    });

    Route::group(["prefix"=>"driver"], function () {

        Route::get('/', [\Modules\Setting\Controllers\DriverController::class, 'index']);
        Route::put('/{id}', [\Modules\Setting\Controllers\DriverController::class, 'updateDriver']);

    });

    Route::group(["prefix"=>"questions"], function () {

        Route::get('/', [\Modules\Setting\Controllers\QuestionSettingController::class, 'index']);
        Route::post('/get-question-for-user', [\Modules\Setting\Controllers\QuestionSettingController::class, 'getUserQuestions']);

    });

});




