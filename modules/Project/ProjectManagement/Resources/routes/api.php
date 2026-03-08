<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectManagement\Controllers\ProjectManagementController;

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class]], function () {
    Route::get('/', [ProjectManagementController::class, 'index']);
    Route::post('/', [ProjectManagementController::class, 'store']);
    Route::post('/export', [ProjectManagementController::class, 'export']);
    Route::get('/widgets', [ProjectManagementController::class, 'widgets']);

    Route::get('/{id}', [ProjectManagementController::class, 'show']);
    Route::put('/{id}', [ProjectManagementController::class, 'update']);
    Route::delete('/{id}', [ProjectManagementController::class, 'delete']);
});
