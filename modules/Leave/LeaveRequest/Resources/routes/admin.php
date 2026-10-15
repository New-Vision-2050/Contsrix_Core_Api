<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeaveRequest\Controllers\AdminLeaveRequestInboxController;
use Modules\RoleAndPermission\Enums\Permission;

/*
|--------------------------------------------------------------------------
| Admin Leave Request Inbox  (prefix: api/v1/admin/leave-requests)
|--------------------------------------------------------------------------
| Approver inbox for leave requests — same criteria as
| api/v1/admin/employee-tasks/inbox (pending workflow step authorized for the
| caller), so no permission middleware is applied here either.
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/inbox', [AdminLeaveRequestInboxController::class, 'inbox']);
    Route::get('/inbox-counts', [AdminLeaveRequestInboxController::class, 'inboxCounts']);

    // Company-wide report (all requests + workflow detail). Permission-gated,
    // unlike the approver inbox above.
    Route::get('/report', [AdminLeaveRequestInboxController::class, 'report'])
        ->permission(Permission::LEAVE_REQUEST_LIST());
    Route::get('/report/{id}', [AdminLeaveRequestInboxController::class, 'reportShow'])
        ->permission(Permission::LEAVE_REQUEST_VIEW());
    Route::patch('/{id}/approve', [AdminLeaveRequestInboxController::class, 'approve']);
    Route::patch('/{id}/reject', [AdminLeaveRequestInboxController::class, 'reject']);
});
