<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeavePolicyCategory\Controllers\LeavePolicyCategoryController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [LeavePolicyCategoryController::class, 'index'])
        ->permission(Permission::LEAVE_POLICY_CATEGORY_LIST(), Permission::PROFILE_CONTRACT_WORK_UPDATE());
    Route::post('/', [LeavePolicyCategoryController::class, 'store'])
        ->permission(Permission::LEAVE_POLICY_CATEGORY_CREATE());

    Route::get('/{id}', [LeavePolicyCategoryController::class, 'show'])
        ->permission(Permission::LEAVE_POLICY_CATEGORY_VIEW(), Permission::LEAVE_POLICY_CATEGORY_UPDATE());
    Route::put('/{id}', [LeavePolicyCategoryController::class, 'update'])
        ->permission(Permission::LEAVE_POLICY_CATEGORY_UPDATE());
    Route::delete('/{id}', [LeavePolicyCategoryController::class, 'delete'])
        ->permission(Permission::LEAVE_POLICY_CATEGORY_DELETE());
});
