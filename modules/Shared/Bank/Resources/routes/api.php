<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Bank\Controllers\BankController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [BankController::class, 'index']);
    Route::post('/', [BankController::class, 'store']);
    Route::get('/{id}', [BankController::class, 'show']);
    Route::put('/{id}', [BankController::class, 'update']);
    Route::delete('/{id}', [BankController::class, 'delete']);
});
