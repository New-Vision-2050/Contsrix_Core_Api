<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeaveRequest\Controllers\UserLeavePolicyController;
use Modules\RoleAndPermission\Enums\Permission;

/*
|--------------------------------------------------------------------------
| User Leave Policy API  (prefix: api/v1/users)
|--------------------------------------------------------------------------
| Assign multiple leave policies to a user and inspect a user's policies
| together with their live yearly balances (entitled / used / pending /
| available).
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/{userId}/leave-policies', [UserLeavePolicyController::class, 'forUser'])
        ->permission(Permission::LEAVE_POLICY_ASSIGN());
    Route::put('/{userId}/leave-policies', [UserLeavePolicyController::class, 'sync'])
        ->permission(Permission::LEAVE_POLICY_ASSIGN());
});
