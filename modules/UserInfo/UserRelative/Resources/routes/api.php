<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserRelative\Controllers\UserRelativeController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [UserRelativeController::class, 'index'])->permission(Permission::PROFILE_MARITAL_STATUS_VIEW());
//    Route::post('/', [UserRelativeController::class, 'store'])->permission(Permission::PROFILE_MARITAL_STATUS_CREATE());
    Route::get('/{id}', [UserRelativeController::class, 'show'])->permission(Permission::PROFILE_MARITAL_STATUS_VIEW());
    Route::put('/{id}', [UserRelativeController::class, 'store'])->permission(Permission::PROFILE_MARITAL_STATUS_UPDATE());
    Route::delete('/{id}', [UserRelativeController::class, 'delete'])->permission(Permission::PROFILE_MARITAL_STATUS_DELETE());
});
