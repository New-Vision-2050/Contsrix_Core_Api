<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeaveRequest\Controllers\LeaveRequestController;
use Modules\RoleAndPermission\Enums\Permission;

/*
|--------------------------------------------------------------------------
| Leave Request API  (prefix: api/v1/leave-requests)
|--------------------------------------------------------------------------
| Mobile employee form + admin list. Approval workflow authorization is
| enforced inside the workflow service (step authorized_user_ids), so the
| approve/reject endpoints intentionally carry no permission middleware.
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    // Employee (mobile)
    Route::post('/', [LeaveRequestController::class, 'store']);
    Route::get('/my', [LeaveRequestController::class, 'myRequests']);
    Route::post('/{id}/cancel', [LeaveRequestController::class, 'cancel']);

    // Approvers (authorization resolved from the workflow step)
    Route::post('/{id}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('/{id}/reject', [LeaveRequestController::class, 'reject']);

    // Admin
    Route::get('/', [LeaveRequestController::class, 'index'])
        ->permission(Permission::LEAVE_REQUEST_LIST());
    Route::get('/{id}', [LeaveRequestController::class, 'show'])
        ->permission(Permission::LEAVE_REQUEST_VIEW());
});
