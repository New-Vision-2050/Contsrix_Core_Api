<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Controllers\SettingController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::post('/', [SettingController::class, 'store']);
    Route::delete('/', [SettingController::class, 'delete']);
});


Route::group(['middleware' => ['auth:api'],"prefix"=>"login-way"], function () {
    Route::get('/', [\Modules\Setting\Controllers\LoginWayController::class, 'index']);
    Route::get('/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'show']);
    Route::post('/', [\Modules\Setting\Controllers\LoginWayController::class, 'store']);
    Route::post('/make-default/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'makeLoginWayDefault']);
    Route::put('/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'update']);
    Route::delete('/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'delete']);
});
Route::group(["prefix"=>"login-way"], function () {

    Route::get('/company-id/{id}', [\Modules\Setting\Controllers\LoginWayController::class, 'getLoginWayByCompanyId']);

});
