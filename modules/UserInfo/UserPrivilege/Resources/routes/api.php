<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserPrivilege\Controllers\UserPrivilegeController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/user/{id}', [UserPrivilegeController::class, 'index'])->permission(Permission::PROFILE_PRIVILEGES_VIEW());
    Route::post('/', [UserPrivilegeController::class, 'store'])->permission(Permission::PROFILE_PRIVILEGES_CREATE());
    Route::get('/{id}', [UserPrivilegeController::class, 'show'])->permission(Permission::PROFILE_PRIVILEGES_VIEW());
    Route::post('/{id}', [UserPrivilegeController::class, 'update'])->permission(Permission::PROFILE_PRIVILEGES_UPDATE());
    Route::delete('/{id}', [UserPrivilegeController::class, 'delete'])->permission(Permission::PROFILE_PRIVILEGES_DELETE());
});
