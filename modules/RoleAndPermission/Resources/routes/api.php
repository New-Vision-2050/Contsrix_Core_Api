<?php

use Illuminate\Support\Facades\Route;
use Modules\RoleAndPermission\Controllers\PermissionController;
use Modules\RoleAndPermission\Controllers\RoleController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], "prefix" => "roles"], function () {
    Route::get('/', [RoleController::class, 'index'])->middleware("permission:" . Permission::ROLE_VIEW());
    Route::post('/', [RoleController::class, 'store'])->middleware("permission:" . Permission::ROLE_CREATE());
    Route::get('/{id}', [RoleController::class, 'show'])->middleware("permission:" . Permission::ROLE_VIEW());
    Route::get('/{id}/permissions', [RoleController::class, 'getPermissions'])->middleware("permission:" . Permission::PERMISSION_VIEW());
    Route::post('/{id}/assign-permissions', [RoleController::class, 'assignPermissionToRole'])->middleware("permission:" . Permission::PERMISSION_ASSIGN());
    Route::put('/{id}', [RoleController::class, 'update'])->middleware("permission:" . Permission::ROLE_EDIT());
    Route::delete('/{id}', [RoleController::class, 'delete'])->middleware("permission:" . Permission::ROLE_DELETE());
    Route::patch('/{id}/status', [RoleController::class, 'setStatus']);
});

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], "prefix" => "permissions"], function () {
    Route::get('/', [PermissionController::class, 'index'])->middleware("permission:" . Permission::PERMISSION_VIEW());
    Route::get('/lookup', [PermissionController::class, 'permissionAsLookup'])->middleware("permission:" . Permission::PERMISSION_VIEW());
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('/{id}', [PermissionController::class, 'show'])->middleware("permission:" . Permission::PERMISSION_VIEW());
    Route::put('/{id}', [PermissionController::class, 'update']);
    Route::delete('/{id}', [PermissionController::class, 'delete']);
    Route::patch('/{id}/status', [PermissionController::class, 'setStatus']);
});
