<?php

namespace App\Listeners;

use Stancl\Tenancy\Tenancy;

class FlushTenancyState
{
    /**
     * Handle the event.
     *
     * Resets tenancy state and static caches between Octane requests 
     * to prevent tenant data leakage and stale data.
     */
    public function handle(): void
    {
        // End current tenancy if active
        $this->flushTenancy();

        // Reset Spatie Permission cache key to default
        $this->flushSpatiePermissions();

        // Disconnect tenant database connections
        $this->disconnectTenantDatabases();

        // Flush presenter static caches
        $this->flushPresenterCaches();

        // Flush management hierarchy presenter state
        $this->flushManagementHierarchyState();
    }

    /**
     * Flush tenancy state.
     */
    protected function flushTenancy(): void
    {
        if (app()->bound(Tenancy::class)) {
            $tenancy = app(Tenancy::class);
            
            if ($tenancy->initialized) {
                $tenancy->end();
            }
        }
    }

    /**
     * Flush Spatie permissions state.
     */
    protected function flushSpatiePermissions(): void
    {
        if (app()->bound(\Spatie\Permission\PermissionRegistrar::class)) {
            $permissionRegistrar = app(\Spatie\Permission\PermissionRegistrar::class);
            $permissionRegistrar->cacheKey = 'spatie.permission.cache';
            $permissionRegistrar->forgetCachedPermissions();
        }
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

    /**
     * Flush static caches in presenter classes.
     * These caches are used for N+1 optimization but must be cleared between requests.
     */
    protected function flushPresenterCaches(): void
    {
        // FilePresenter caches
        if (class_exists(\Modules\ArchiveLibrary\File\Presenters\FilePresenter::class)) {
            if (method_exists(\Modules\ArchiveLibrary\File\Presenters\FilePresenter::class, 'clearAuditsCache')) {
                \Modules\ArchiveLibrary\File\Presenters\FilePresenter::clearAuditsCache();
            }
            if (method_exists(\Modules\ArchiveLibrary\File\Presenters\FilePresenter::class, 'clearFavouritesCache')) {
                \Modules\ArchiveLibrary\File\Presenters\FilePresenter::clearFavouritesCache();
            }
        }

        // FolderPresenter caches
        if (class_exists(\Modules\ArchiveLibrary\Folder\Presenters\FolderPresenter::class)) {
            if (method_exists(\Modules\ArchiveLibrary\Folder\Presenters\FolderPresenter::class, 'clearAuditsCache')) {
                \Modules\ArchiveLibrary\Folder\Presenters\FolderPresenter::clearAuditsCache();
            }
            if (method_exists(\Modules\ArchiveLibrary\Folder\Presenters\FolderPresenter::class, 'clearFileSizesCache')) {
                \Modules\ArchiveLibrary\Folder\Presenters\FolderPresenter::clearFileSizesCache();
            }
        }
    }

    /**
     * Flush management hierarchy presenter static state.
     * These static flags control tree generation behavior and must be reset.
     */
    protected function flushManagementHierarchyState(): void
    {
        // ManagementHierarchyUserTreePresenter
        if (class_exists(\Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyUserTreePresenter::class)) {
            $presenter = \Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyUserTreePresenter::class;
            
            // Reset included users tracking
            if (method_exists($presenter, 'resetIncludedUsers')) {
                $presenter::resetIncludedUsers();
            }
            
            // Reset flags to defaults
            if (method_exists($presenter, 'setIncludeManagers')) {
                $presenter::setIncludeManagers(true);
            }
            if (method_exists($presenter, 'setIncludeDeputyManagers')) {
                $presenter::setIncludeDeputyManagers(true);
            }
            if (method_exists($presenter, 'setIncludeDirectChildren')) {
                $presenter::setIncludeDirectChildren(true);
            }
            if (method_exists($presenter, 'setSkipManagementMainNodes')) {
                $presenter::setSkipManagementMainNodes(false);
            }
        }

        // ManagementHierarchyTreePresenter
        if (class_exists(\Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyTreePresenter::class)) {
            $presenter = \Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyTreePresenter::class;
            
            if (method_exists($presenter, 'setSkipManagementMainNodes')) {
                $presenter::setSkipManagementMainNodes(false);
            }
        }
    }
}
