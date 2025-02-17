<?php

use Illuminate\Support\Facades\Route;
use Modules\Audit\Controllers\AuditController;

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [AuditController::class, 'index']);
    Route::get('/{id}', [AuditController::class, 'show']);
    Route::delete('/{id}', [AuditController::class, 'delete']);
});
