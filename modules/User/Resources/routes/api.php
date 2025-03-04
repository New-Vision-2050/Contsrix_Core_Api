<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [UserController::class, 'index'])->middleware("permission:user.list")->name("users.list");
    Route::post('/', [UserController::class, 'store'])->middleware("permission:user.create");
    Route::get('/me', [UserController::class, 'me'])->middleware("permission:user.list");
    Route::get('/my-permissions', [UserController::class, 'getMyPermissions']);
    Route::get('/my-roles', [UserController::class, 'getMyRoles']);

    Route::get('/{id}', [UserController::class, 'show'])->middleware("permission:user.show");
    Route::get('/{id}/roles', [UserController::class, 'getRoles']);
    Route::get('/{id}/permissions', [UserController::class, 'getPermissions']);
    Route::get('/{id}/audits', [UserController::class, 'getAudites']);
    Route::put('/{id}', [UserController::class, 'update'])->middleware("permission:user.update");
    Route::post('/{id}/assign-roles', [UserController::class, 'assignRolesForUser']);

    Route::delete('/{id}', [UserController::class, 'delete'])->middleware("permission:user.delete");
});
