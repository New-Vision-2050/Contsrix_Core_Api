<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\BankTypeAccount\Controllers\BankTypeAccountController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [BankTypeAccountController::class, 'index']);
    Route::post('/', [BankTypeAccountController::class, 'store']);
    Route::get('/{id}', [BankTypeAccountController::class, 'show']);
    Route::put('/{id}', [BankTypeAccountController::class, 'update']);
    Route::delete('/{id}', [BankTypeAccountController::class, 'delete']);
});
