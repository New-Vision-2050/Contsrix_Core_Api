<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeaveType\Controllers\LeaveTypeController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [LeaveTypeController::class, 'index'])
        ->permission(Permission::LEAVE_TYPE_LIST());
    Route::post('/', [LeaveTypeController::class, 'store'])
        ->permission(Permission::LEAVE_TYPE_CREATE());
    Route::post('/export', [LeaveTypeController::class, 'export'])
        ->permission(Permission::LEAVE_TYPE_EXPORT());

    Route::get('/{id}', [LeaveTypeController::class, 'show'])
        ->permission(Permission::LEAVE_TYPE_VIEW(),Permission::LEAVE_TYPE_UPDATE());
    Route::put('/{id}', [LeaveTypeController::class, 'update'])
        ->permission(Permission::LEAVE_TYPE_UPDATE());
    Route::delete('/{id}', [LeaveTypeController::class, 'delete'])
        ->permission(Permission::LEAVE_TYPE_DELETE());
});
