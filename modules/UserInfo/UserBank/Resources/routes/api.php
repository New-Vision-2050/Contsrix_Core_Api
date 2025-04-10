<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserBank\Controllers\UserBankController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [UserBankController::class, 'index']);
    Route::post('/', [UserBankController::class, 'store']);
    Route::get('/{id}', [UserBankController::class, 'show']);
    Route::put('/{id}', [UserBankController::class, 'update']);
    Route::delete('/{id}', [UserBankController::class, 'delete']);
});
