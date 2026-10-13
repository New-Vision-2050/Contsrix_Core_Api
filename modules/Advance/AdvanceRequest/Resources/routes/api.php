<?php

use Illuminate\Support\Facades\Route;
use Modules\Advance\AdvanceRequest\Controllers\AdvanceRequestController;
use Modules\RoleAndPermission\Enums\Permission;

/*
|--------------------------------------------------------------------------
| Advance Request API  (prefix: api/v1/advance-requests)
|--------------------------------------------------------------------------
| Employee form + admin list (طلبات السلف). Approval workflow authorization is
| enforced inside the workflow service (step authorized_user_ids), so the
| approve/reject endpoints intentionally carry no permission middleware.
*/
Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {

    // Employee (mobile / self-service)
    Route::post('/', [AdvanceRequestController::class, 'store']);
    Route::get('/my', [AdvanceRequestController::class, 'myRequests']);
    Route::post('/{id}/cancel', [AdvanceRequestController::class, 'cancel']);

    // Approvers (authorization resolved from the workflow step)
    Route::post('/{id}/approve', [AdvanceRequestController::class, 'approve']);
    Route::post('/{id}/reject', [AdvanceRequestController::class, 'reject']);

    // Admin
    Route::get('/', [AdvanceRequestController::class, 'index'])
        ->permission(Permission::ADVANCE_REQUEST_LIST());
    Route::get('/{id}', [AdvanceRequestController::class, 'show'])
        ->permission(Permission::ADVANCE_REQUEST_VIEW());
});
