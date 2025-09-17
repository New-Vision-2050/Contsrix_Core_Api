<?php

use Illuminate\Support\Facades\Route;
use Modules\UserInfo\UserSalary\Controllers\UserSalaryController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/user/{id}', [UserSalaryController::class, 'index'])->permission(Permission::PROFILE_SALARY_INFO_VIEW());
    Route::post('/', [UserSalaryController::class, 'store'])->permission(Permission::PROFILE_SALARY_INFO_UPDATE());
    Route::get('/{id}', [UserSalaryController::class, 'show'])->permission(Permission::PROFILE_SALARY_INFO_VIEW());
    Route::put('/{id}', [UserSalaryController::class, 'update'])->permission(Permission::PROFILE_SALARY_INFO_UPDATE());
    // Route::delete('/', [UserSalaryController::class, 'delete']);
});
