<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [UserController::class, 'index'])->name("users.list");
    Route::post('/', [UserController::class, 'store']);
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/my-permissions', [UserController::class, 'getMyPermissions']);
    Route::get('/my-roles', [UserController::class, 'getMyRoles']);

    Route::get('/{id}', [UserController::class, 'show']);
//    Route::get('/{id}/roles', [UserController::class, 'roles']);
//    Route::get('/{id}/permissions', [UserController::class, 'permissions']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::post('/{id}/assign-roles', [UserController::class, 'assignRolesForUser']);

    Route::delete('/{id}', [UserController::class, 'delete']);
});
