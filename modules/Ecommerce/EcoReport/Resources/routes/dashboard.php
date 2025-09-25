<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\EcoReport\Controllers\Dashboard\EcoReportDashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard API Routes - EcoReport
|--------------------------------------------------------------------------
|
| Admin dashboard routes for reports and analytics
| Requires admin authentication and permissions
|
*/

Route::group(['middleware' => ['auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class], 'prefix' => 'dashboard/reports'], function () {
    
    Route::get('/', [EcoReportDashboardController::class, 'getDashboard']);
    Route::get('/summary', [EcoReportDashboardController::class, 'getSummaryMetrics']);
    Route::get('/orders', [EcoReportDashboardController::class, 'getOrdersData']);
    Route::get('/shipping', [EcoReportDashboardController::class, 'getShippingMethods']);
    Route::get('/payment', [EcoReportDashboardController::class, 'getPaymentMethods']);
    Route::get('/order-status', [EcoReportDashboardController::class, 'getOrderStatusSummary']);
    Route::get('/processing-time', [EcoReportDashboardController::class, 'getAverageProcessingTime']);
    Route::get('/delivery-time', [EcoReportDashboardController::class, 'getAverageDeliveryTime']);
    Route::get('/warehouse-sales', [EcoReportDashboardController::class, 'getWarehouseSalesDataPaginated']);
    Route::get('/conversion-rates', [EcoReportDashboardController::class, 'getConversionRates']);
    Route::get('/discount-sections', [EcoReportDashboardController::class, 'getDiscountSectionsData']);
    Route::get('/metrics', [EcoReportDashboardController::class, 'getDashboardMetrics']);
    Route::get('/products', [EcoReportDashboardController::class, 'getProductsManagement']);
    Route::get('/client-sections', [EcoReportDashboardController::class, 'getDashboardClient']);
    Route::post('/clear-cache', [EcoReportDashboardController::class, 'clearCache']);
});
