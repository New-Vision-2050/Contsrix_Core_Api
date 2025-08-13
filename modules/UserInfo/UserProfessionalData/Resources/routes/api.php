<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserProfessionalData\Controllers\UserProfessionalDataController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('user/{id}', [UserProfessionalDataController::class, 'index']);
    Route::post('/', [UserProfessionalDataController::class, 'store']);
    Route::get('/{id}', [UserProfessionalDataController::class, 'show'])->permission(Permission::PROFILE_CONTRACT_WORK_VIEW());
    Route::put('/{id}', [UserProfessionalDataController::class, 'update'])->permission(Permission::PROFILE_CONTRACT_WORK_VIEW());
    Route::delete('/{id}', [UserProfessionalDataController::class, 'delete'])->permission(Permission::PROFILE_CONTRACT_WORK_VIEW());
});
