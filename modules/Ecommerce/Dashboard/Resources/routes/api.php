<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Dashboard\Controllers\DashboardController;

Route::group(['middleware' => ['auth:api']], function () {
    // Dashboard main routes
    Route::get('/', [DashboardController::class, 'getMainDashboard']);
    Route::get('/orders-chart', [DashboardController::class, 'getOrdersChart']);
    Route::get('/warehouses-table', [DashboardController::class, 'getWarehousesTable']);
});
