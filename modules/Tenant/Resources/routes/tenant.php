<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Controllers\TenantAuthController;

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

// Authentication routes (no auth required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [TenantAuthController::class, 'login']);
    Route::post('/refresh', [TenantAuthController::class, 'refresh']);
});

// Debug routes (no auth required)
Route::prefix('debug')->group(function () {
    Route::get('/tenant', function () {
        return response()->json([
            'tenant_exists' => tenant() ? true : false,
            'tenant_id' => tenant() ? tenant()->id : null,
            'company_id' => tenant() ? tenant()->company_id : null,
            'domain' => request()->getHost()
        ]);
    });

    Route::get('/user/{id}', function ($id) {
        $user = \Modules\Tenant\Models\TenantUser::find($id);
        return response()->json([
            'user_exists' => $user ? true : false,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'companies' => $user ? $user->companies->pluck('id')->toArray() : []
        ]);
    });
});

// Protected tenant routes
Route::middleware(['tenant.auth'])->group(function () {
    // Authentication routes that require authentication
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [TenantAuthController::class, 'logout']);
        Route::get('/me', [TenantAuthController::class, 'me']);
    });

    // Tenant information
    Route::prefix('tenant')->group(function () {
        Route::get('/info', function () {
            $tenant = tenant();
            return response()->json([
                'id' => $tenant->id,
                'name' => $tenant->name,
                'company_id' => $tenant->company_id,
                'domain' => request()->getHost(),
            ]);
        });
    });

    // Company user routes
    Route::prefix('company-users')->group(function () {
        // These routes will access tenant-specific data
        // The tenant database connection will be used automatically
    });
});
