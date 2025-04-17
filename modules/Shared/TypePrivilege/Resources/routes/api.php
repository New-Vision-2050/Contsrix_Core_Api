<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\TypePrivilege\Controllers\TypePrivilegeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [TypePrivilegeController::class, 'index']);
    Route::post('/', [TypePrivilegeController::class, 'store']);
    Route::get('/{id}', [TypePrivilegeController::class, 'show']);
    Route::put('/{id}', [TypePrivilegeController::class, 'update']);
    Route::delete('/{id}', [TypePrivilegeController::class, 'delete']);
});
