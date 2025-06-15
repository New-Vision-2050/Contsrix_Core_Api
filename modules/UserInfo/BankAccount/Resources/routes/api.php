<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\BankAccount\Controllers\BankAccountController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [BankAccountController::class, 'index']);
    Route::post('/', [BankAccountController::class, 'store']);
    Route::get('/{id}', [BankAccountController::class, 'show']);
    Route::put('/{id}', [BankAccountController::class, 'update']);
    Route::put('/{id}/type', [BankAccountController::class, 'updateType']);
    Route::delete('/{id}', [BankAccountController::class, 'delete']);
});
