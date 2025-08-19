<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeavePolicy\Controllers\LeavePolicyController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [LeavePolicyController::class, 'index'])
        ->permission(Permission::LEAVE_POLICY_LIST());
    Route::post('/', [LeavePolicyController::class, 'store'])
        ->permission(Permission::LEAVE_POLICY_CREATE());
    Route::post('/export', [LeavePolicyController::class, 'export'])
        ->permission(Permission::LEAVE_POLICY_EXPORT());

    Route::get('/{id}', [LeavePolicyController::class, 'show'])
        ->permission(Permission::LEAVE_POLICY_VIEW());
    Route::put('/{id}', [LeavePolicyController::class, 'update'])
        ->permission(Permission::LEAVE_POLICY_UPDATE());
    Route::put('/{id}/rollover-allowed', [LeavePolicyController::class, 'updateRolloverAllowed'])
        ->permission(Permission::LEAVE_POLICY_UPDATE());
    Route::put('/{id}/half-day-allowed', [LeavePolicyController::class, 'updateHalfDayAllowed'])
        ->permission(Permission::LEAVE_POLICY_UPDATE());
    Route::delete('/{id}', [LeavePolicyController::class, 'delete'])
        ->permission(Permission::LEAVE_POLICY_DELETE());
});
