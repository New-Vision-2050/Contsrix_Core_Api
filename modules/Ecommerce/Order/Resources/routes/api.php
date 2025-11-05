<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Order\Controllers\OrderController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::post('/export', [OrderController::class, 'export']);

    // Statistics
    Route::get('/statistics', [OrderController::class, 'getStatistics']);

    // Bulk operations
    Route::patch('/bulk-status', [OrderController::class, 'bulkUpdateStatus']);

    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}', [OrderController::class, 'update']);
    Route::delete('/{id}', [OrderController::class, 'delete']);
    
    // Status management routes
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
    Route::get('/{id}/status-history', [OrderController::class, 'getStatusHistory']);
    Route::get('/{id}/available-statuses', [OrderController::class, 'getAvailableStatuses']);
});
