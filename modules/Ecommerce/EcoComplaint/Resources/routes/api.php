<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoComplaint\Controllers\EcoComplaintController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(callback: function () {
    Route::get('/', [EcoComplaintController::class, 'index']);
    Route::post('/', [EcoComplaintController::class, 'store']);
    Route::post('/export', [EcoComplaintController::class, 'export']);

    Route::get('/{id}', [EcoComplaintController::class, 'show']);
    Route::put('/{id}', [EcoComplaintController::class, 'update']);
    Route::delete('/{id}', [EcoComplaintController::class, 'delete']);
});
