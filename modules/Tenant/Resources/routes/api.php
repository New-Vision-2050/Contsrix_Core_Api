<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Controllers\CrossTenantReportController;
use Modules\Tenant\Controllers\TenantController;

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

// Tenant management routes (no tenant context required)
Route::prefix('tenants')->group(function () {
    // Create a new tenant
    Route::post('/', [TenantController::class, 'create']);
    
    // Cross-tenant reporting routes (admin access only)
    Route::prefix('reports')->middleware(['auth:api', 'role:admin'])->group(function () {
        // Get all tenants
        Route::get('/', [CrossTenantReportController::class, 'tenants']);
        
        // Get tenants summary
        Route::get('/summary', [CrossTenantReportController::class, 'tenantsSummary']);
        
        // Get users across all tenants
        Route::get('/users', [CrossTenantReportController::class, 'usersAcrossTenants']);
        
        // Get tenant activity
        Route::get('/activity', [CrossTenantReportController::class, 'tenantActivity']);
        
        // Get user activity across tenants
        Route::get('/users/{userId}/activity', [CrossTenantReportController::class, 'userActivity']);
    });
});

// Tenant-specific routes (tenant context required)
Route::middleware(['tenant', 'auth:api'])->group(function () {
    // Get current tenant information
    Route::get('/tenant', [TenantController::class, 'current']);
    
    // Update tenant information
    Route::put('/tenant', [TenantController::class, 'update']);
    
    // Get tenant statistics
    Route::get('/tenant/statistics', [TenantController::class, 'statistics']);
    
    // Project routes
    Route::apiResource('projects', \Modules\Tenant\Controllers\ProjectController::class);
    
    // Task routes
    Route::apiResource('tasks', \Modules\Tenant\Controllers\TaskController::class);
    
    // Document routes
    Route::apiResource('documents', \Modules\Tenant\Controllers\DocumentController::class);
    Route::get('documents/{id}/download', [\Modules\Tenant\Controllers\DocumentController::class, 'download']);
});