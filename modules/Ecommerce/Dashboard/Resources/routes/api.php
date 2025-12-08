<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Dashboard\Controllers\DashboardController;
use Modules\RoleAndPermission\Enums\Permission;

Route::group([
    'middleware' => [
        'auth:api', 
        \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class
    ]
], function () {
    // Dashboard main routes
    Route::get('/', [DashboardController::class, 'getMainDashboard'])
        ->permission(Permission::ECOMMERCE_DASHBOARD_VIEW());
    Route::get('/orders-chart', [DashboardController::class, 'getOrdersChart'])
        ->permission(Permission::ECOMMERCE_DASHBOARD_ORDERS_CHART());
    Route::get('/warehouses-table', [DashboardController::class, 'getWarehousesTable'])
        ->permission(Permission::ECOMMERCE_DASHBOARD_WAREHOUSES_TABLE());
});
