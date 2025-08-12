<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserStatus\Controllers\UserStatusController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [UserStatusController::class, 'index'])->permission(Permission::PROFILE_PERSONAL_INFO_VIEW());
    Route::post('/activation/{id}', [UserStatusController::class, 'updateStatus'])->permission(Permission::PROFILE_PERSONAL_INFO_UPDATE());
    Route::post('/password/{id}', [UserStatusController::class, 'updatePassword'])->permission(Permission::PROFILE_PERSONAL_INFO_UPDATE());
    // Route::post('/activation/{id}', [UserStatusController::class, 'updateStatus']);
    // Route::delete('/{id}', [UserStatusController::class, 'delete']);
});
