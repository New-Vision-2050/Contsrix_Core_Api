<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Controllers\PermissionController;
use Modules\RoleAndPermission\Controllers\RoleAndPermissionController;
use Modules\RoleAndPermission\Controllers\RoleController;

Route::group(['middleware' => ['auth:sanctum'],"prefix"=>"roles"], function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('/{id}', [RoleController::class, 'show']);
    Route::get('/{id}/permissions', [RoleController::class, 'getPermissions']);
    Route::post('/{id}/assign-permissions', [RoleController::class, 'assignPermissionToRole']);
    Route::put('/{id}', [RoleController::class, 'update']);
    Route::delete('/{id}', [RoleController::class, 'delete']);
});


Route::group(['middleware' => ['auth:sanctum'],"prefix"=>"permissions"], function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);

    Route::get('/{id}', [PermissionController::class, 'show']);
    Route::put('/{id}', [PermissionController::class, 'update']);
    Route::delete('/{id}', [PermissionController::class, 'delete']);

});
