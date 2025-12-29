<?php

namespace App\Listeners;

use Stancl\Tenancy\Tenancy;

class FlushTenancyState
{
    /**
     * Handle the event.
     *
     * Resets tenancy state between Octane requests to prevent tenant data leakage.
     */
    public function handle(): void
    {
        // End current tenancy if active
        if (app()->bound(Tenancy::class)) {
            $tenancy = app(Tenancy::class);
            
            if ($tenancy->initialized) {
                $tenancy->end();
            }
        }

        // Reset Spatie Permission cache key to default
        if (app()->bound(\Spatie\Permission\PermissionRegistrar::class)) {
            $permissionRegistrar = app(\Spatie\Permission\PermissionRegistrar::class);
            $permissionRegistrar->cacheKey = 'spatie.permission.cache';
            $permissionRegistrar->forgetCachedPermissions();
        }

        // Disconnect tenant database connections
        $this->disconnectTenantDatabases();
    }

    /**
     * Disconnect any tenant-specific database connections.
     */
    protected function disconnectTenantDatabases(): void
    {
        $tenantConnectionName = config('tenancy.database.tenant_connection', 'tenant');
        
        try {
            if (app('db')->getConnections()) {
                // Disconnect tenant connection if it exists
                if (isset(app('db')->getConnections()[$tenantConnectionName])) {
                    app('db')->disconnect($tenantConnectionName);
                }
            }
        } catch (\Exception $e) {
            // Silently ignore if connection doesn't exist
        }
    }
}
