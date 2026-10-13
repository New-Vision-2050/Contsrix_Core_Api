<?php

use Illuminate\Support\Facades\Route;
use Modules\Advance\AdvanceRequest\Controllers\AdminAdvanceRequestInboxController;

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
    Route::patch('/{id}/approve', [AdminAdvanceRequestInboxController::class, 'approve']);
    Route::patch('/{id}/reject', [AdminAdvanceRequestInboxController::class, 'reject']);
});
