<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeaveType\Controllers\LeaveTypeController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [LeaveTypeController::class, 'index']);
    Route::post('/', [LeaveTypeController::class, 'store']);
    Route::get('/{id}', [LeaveTypeController::class, 'show']);
    Route::put('/{id}', [LeaveTypeController::class, 'update']);
    Route::delete('/{id}', [LeaveTypeController::class, 'delete']);
});
