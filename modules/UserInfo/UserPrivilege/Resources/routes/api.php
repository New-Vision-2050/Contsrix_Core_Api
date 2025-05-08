<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserPrivilege\Controllers\UserPrivilegeController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserPrivilegeController::class, 'index']);
    Route::post('/', [UserPrivilegeController::class, 'store']);
    Route::get('/{id}', [UserPrivilegeController::class, 'show']);
    Route::post('/{id}', [UserPrivilegeController::class, 'update']);
    Route::delete('/{id}', [UserPrivilegeController::class, 'delete']);
});
