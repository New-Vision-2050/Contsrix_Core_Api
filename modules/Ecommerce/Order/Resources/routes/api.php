<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Order\Controllers\OrderController;
use Modules\RoleAndPermission\Enums\Permission;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    Route::get('/', [OrderController::class, 'index'])
        ->permission(Permission::ECOMMERCE_ORDER_LIST());
    Route::post('/', [OrderController::class, 'store'])
        ->permission(Permission::ECOMMERCE_ORDER_CREATE());
    Route::post('/export', [OrderController::class, 'export'])
        ->permission(Permission::ECOMMERCE_ORDER_EXPORT());

    // Statistics
    Route::get('/statistics', [OrderController::class, 'getStatistics'])
        ->permission(Permission::ECOMMERCE_ORDER_LIST());

    // Bulk operations
    Route::patch('/bulk-status', [OrderController::class, 'bulkUpdateStatus'])
        ->permission(Permission::ECOMMERCE_ORDER_UPDATE_STATUS());

    Route::get('/{id}', [OrderController::class, 'show'])
        ->permission(Permission::ECOMMERCE_ORDER_VIEW(), Permission::ECOMMERCE_ORDER_UPDATE());
    Route::put('/{id}', [OrderController::class, 'update'])
        ->permission(Permission::ECOMMERCE_ORDER_UPDATE());
    Route::delete('/{id}', [OrderController::class, 'delete'])
        ->permission(Permission::ECOMMERCE_ORDER_DELETE());
    
    // Status management routes
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])
        ->permission(Permission::ECOMMERCE_ORDER_UPDATE_STATUS());
    Route::get('/{id}/status-history', [OrderController::class, 'getStatusHistory'])
        ->permission(Permission::ECOMMERCE_ORDER_VIEW());
    Route::get('/{id}/available-statuses', [OrderController::class, 'getAvailableStatuses'])
        ->permission(Permission::ECOMMERCE_ORDER_VIEW());
});
