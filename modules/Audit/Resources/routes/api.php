<?php

use Illuminate\Support\Facades\Route;
use Modules\Audit\Controllers\AuditController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [AuditController::class, 'index']);
    Route::post('/', [AuditController::class, 'store']);
    Route::get('/{id}', [AuditController::class, 'show']);
    Route::put('/{id}', [AuditController::class, 'update']);
    Route::delete('/{id}', [AuditController::class, 'delete']);
});
