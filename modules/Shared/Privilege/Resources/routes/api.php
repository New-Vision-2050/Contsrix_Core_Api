<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Privilege\Controllers\PrivilegeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [PrivilegeController::class, 'index']);
    Route::post('/', [PrivilegeController::class, 'store']);
    Route::get('/{id}', [PrivilegeController::class, 'show']);
    Route::put('/{id}', [PrivilegeController::class, 'update']);
    Route::delete('/{id}', [PrivilegeController::class, 'delete']);
});
