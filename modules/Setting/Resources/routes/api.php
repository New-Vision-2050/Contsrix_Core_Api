<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Controllers\SettingController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::post('/', [SettingController::class, 'store']);
    Route::delete('/', [SettingController::class, 'delete']);
});
