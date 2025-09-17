<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\EmploymentContract\Controllers\EmploymentContractController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [EmploymentContractController::class, 'index'])->permission(Permission::PROFILE_CONTRACT_WORK_VIEW());
    Route::post('/', [EmploymentContractController::class, 'store'])->permission(Permission::PROFILE_CONTRACT_WORK_UPDATE());
    Route::get('/{id}', [EmploymentContractController::class, 'show'])->permission(Permission::PROFILE_CONTRACT_WORK_VIEW());
    Route::put('/{id}', [EmploymentContractController::class, 'update'])->permission(Permission::PROFILE_CONTRACT_WORK_UPDATE());
    Route::delete('/{id}', [EmploymentContractController::class, 'delete'])->permission(Permission::PROFILE_CONTRACT_WORK_UPDATE());
});
