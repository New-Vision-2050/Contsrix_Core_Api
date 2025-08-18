<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\LeavePolicy\Controllers\LeavePolicyController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [LeavePolicyController::class, 'index']);
    Route::post('/', [LeavePolicyController::class, 'store']);
    Route::get('/{id}', [LeavePolicyController::class, 'show']);
    Route::put('/{id}', [LeavePolicyController::class, 'update']);
    Route::delete('/{id}', [LeavePolicyController::class, 'delete']);
});
