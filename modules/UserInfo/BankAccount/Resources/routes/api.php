<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\BankAccount\Controllers\BankAccountController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [BankAccountController::class, 'index']);
    Route::post('/', [BankAccountController::class, 'store']);
    Route::get('/{id}', [BankAccountController::class, 'show']);
    Route::put('/{id}', [BankAccountController::class, 'update']);
    Route::delete('/{id}', [BankAccountController::class, 'delete']);
});
