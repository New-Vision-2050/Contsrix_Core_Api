<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoReport\Controllers\EcoReportController;
use Modules\Ecommerce\EcoReport\Controllers\DashboardController;

Route::middleware(['auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])->group(function (): void {
    // Standard CRUD routes
    // Route::get('/', [EcoReportController::class, 'index']);
    // Route::post('/', [EcoReportController::class, 'store']);
    // Route::post('/export', [EcoReportController::class, 'export']);

    // Route::get('/{id}', [EcoReportController::class, 'show']);
    // Route::put('/{id}', [EcoReportController::class, 'update']);
    // Route::delete('/{id}', [EcoReportController::class, 'delete']);

    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'getDashboard']);
        Route::get('/summary', [DashboardController::class, 'getSummaryMetrics']);
        Route::get('/orders', [DashboardController::class, 'getOrdersData']);
        Route::get('/shipping', [DashboardController::class, 'getShippingMethods']);
        Route::get('/payment', [DashboardController::class, 'getPaymentMethods']);
        Route::get('/order-status', [DashboardController::class, 'getOrderStatusSummary']);
        Route::get('/processing-time', [DashboardController::class, 'getAverageProcessingTime']);
        Route::get('/delivery-time', [DashboardController::class, 'getAverageDeliveryTime']);
        Route::get('/warehouse-sales', [DashboardController::class, 'getWarehouseSalesDataPaginated']);
        Route::get('/conversion-rates', [DashboardController::class, 'getConversionRates']);
        Route::get('/discount-sections', [DashboardController::class, 'getDiscountSectionsData']);
        Route::post('/clear-cache', [DashboardController::class, 'clearCache']);
    });
});
