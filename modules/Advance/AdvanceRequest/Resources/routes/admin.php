<?php

use Illuminate\Support\Facades\Route;
use Modules\Advance\AdvanceRequest\Controllers\AdminAdvanceRequestInboxController;
use Modules\RoleAndPermission\Enums\Permission;

/*
|--------------------------------------------------------------------------
| Admin Advance Request Inbox  (prefix: api/v1/admin/advance-requests)
|--------------------------------------------------------------------------
| Approver inbox for advance requests — same criteria as
| api/v1/admin/employee-tasks/inbox (pending workflow step authorized for the
| caller), so no permission middleware is applied here either.
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/inbox', [AdminAdvanceRequestInboxController::class, 'inbox']);
    Route::get('/inbox-counts', [AdminAdvanceRequestInboxController::class, 'inboxCounts']);

    // Company-wide report (all requests + workflow detail). Permission-gated,
    // unlike the approver inbox above.
    Route::get('/report', [AdminAdvanceRequestInboxController::class, 'report'])
        ->permission(Permission::ADVANCE_REQUEST_LIST());
    Route::get('/report/{id}', [AdminAdvanceRequestInboxController::class, 'reportShow'])
        ->permission(Permission::ADVANCE_REQUEST_VIEW());
    Route::patch('/{id}/approve', [AdminAdvanceRequestInboxController::class, 'approve']);
    Route::patch('/{id}/reject', [AdminAdvanceRequestInboxController::class, 'reject']);
});
