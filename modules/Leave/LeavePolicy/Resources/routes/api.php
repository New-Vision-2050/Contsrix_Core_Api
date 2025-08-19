<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeavePolicy\Controllers\LeavePolicyController;

Route::group(['middleware' => ['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [LeavePolicyController::class, 'index']);
    Route::post('/', [LeavePolicyController::class, 'store']);
    Route::post('/export', [LeavePolicyController::class, 'export']);

    Route::get('/{id}', [LeavePolicyController::class, 'show']);
    Route::put('/{id}', [LeavePolicyController::class, 'update']);
    Route::put('/{id}/rollover-allowed', [LeavePolicyController::class, 'updateRolloverAllowed']);
    Route::put('/{id}/half-day-allowed', [LeavePolicyController::class, 'updateHalfDayAllowed']);
    Route::delete('/{id}', [LeavePolicyController::class, 'delete']);
});
