<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Controllers\PermissionController;
use Modules\RoleAndPermission\Controllers\RoleController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('permissions', [PermissionController::class, 'index'])->permission(Permission::PERMISSION_LIST());
    Route::get('permissions/lookup', [PermissionController::class, 'permissionAsLookup']);
    Route::post('permissions', [PermissionController::class, 'store'])->permission(Permission::PERMISSION_CREATE());
    Route::get('permissions/{id}', [PermissionController::class, 'show'])->permission(Permission::PERMISSION_VIEW());
    Route::put('permissions/{id}', [PermissionController::class, 'update'])->permission(Permission::PERMISSION_EDIT());
    Route::delete('permissions/{id}', [PermissionController::class, 'delete'])->permission(Permission::PERMISSION_DELETE());
    Route::patch('permissions/{id}/status', [PermissionController::class, 'setStatus'])->permission(Permission::PERMISSION_EDIT());

    Route::group(['prefix' => 'roles'], function () {
        Route::get('/', [RoleController::class, 'index'])->permission(Permission::ROLE_VIEW());
        Route::get('/widgets', [RoleController::class, 'getRoleWidgetsData'])->permission(Permission::ROLE_VIEW());
        Route::post('/', [RoleController::class, 'store'])->permission(Permission::ROLE_CREATE());
        Route::get('/{id}', [RoleController::class, 'show'])->permission(Permission::ROLE_VIEW());
        Route::get('/{id}/permissions', [RoleController::class, 'getPermissions'])->permission(Permission::PERMISSION_VIEW());
        Route::post('/{id}/assign-permissions', [RoleController::class, 'assignPermissionToRole']);
        Route::put('/{id}', [RoleController::class, 'update'])->permission(Permission::ROLE_EDIT());
        Route::delete('/{id}', [RoleController::class, 'delete'])->permission(Permission::ROLE_DELETE());
        Route::patch('/{id}/status', [RoleController::class, 'setStatus'])->permission(Permission::ROLE_EDIT());
    });
});
