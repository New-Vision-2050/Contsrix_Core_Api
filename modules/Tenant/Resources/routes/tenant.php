<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here is where you can register tenant-specific API routes. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api", "tenancy", and "prevent-access-from-central-domains" middleware.
|
*/

// These routes will only be accessible within a tenant context
// For example: https://tenant-domain.com/api/tenant/...

Route::prefix('tenant')->middleware(['auth:api'])->group(function () {
    // Get current tenant information
    Route::get('/info', function () {
        $tenant = tenant();
        return response()->json([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => request()->getHost(),
        ]);
    });
    
    // Add more tenant-specific routes here
});

// You can also define routes for other modules that should be tenant-aware
// For example, CompanyUser routes within a tenant context
Route::prefix('company-users')->middleware(['auth:api'])->group(function () {
    // These routes will access tenant-specific data
    // The tenant database connection will be used automatically
});