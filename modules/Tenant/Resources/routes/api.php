<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Controllers\TenantController;
use Modules\Tenant\Controllers\TenantReportingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('tenants')->middleware(['auth:api'])->group(function () {
    // List all tenants
    Route::get('/', [TenantController::class, 'index']);
    
    // Create a new tenant for a company
    Route::post('/companies/{companyId}', [TenantController::class, 'store']);
    
    // Get tenant details
    Route::get('/{id}', [TenantController::class, 'show'])->where('id', '[0-9a-f\-]+');
    
    // Delete a tenant
    Route::delete('/{id}', [TenantController::class, 'destroy'])->where('id', '[0-9a-f\-]+');
    
    // Reporting routes
    Route::prefix('reports')->group(function () {
        // Get user count by tenant
        Route::get('/users', [TenantReportingController::class, 'getUserCountByTenant']);
        
        // Get dashboard statistics
        Route::get('/dashboard', [TenantReportingController::class, 'getDashboardStats']);
        
        // Get tenant health report
        Route::get('/health', [TenantReportingController::class, 'getTenantHealth']);
    });
});
