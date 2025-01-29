<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Controllers\RoleAndPermissionController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [RoleAndPermissionController::class, 'index']);
    Route::post('/', [RoleAndPermissionController::class, 'store']);
    Route::get('/{id}', [RoleAndPermissionController::class, 'show']);
    Route::put('/{id}', [RoleAndPermissionController::class, 'update']);
    Route::delete('/{id}', [RoleAndPermissionController::class, 'delete']);
});
