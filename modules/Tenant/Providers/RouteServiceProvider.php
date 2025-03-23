<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Tenant\Controllers';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapTenantRoutes();
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(module_path('Tenant', '/Resources/routes/api.php'));
    }

    /**
     * Define the "tenant" routes for the application.
     *
     * These routes are typically stateless and are accessed via tenant domains.
     *
     * @return void
     */
    protected function mapTenantRoutes()
    {
        // Define routes that should be accessible only within tenant context
        Route::prefix('api')
            ->middleware(['api', 'tenancy', 'prevent-access-from-central-domains'])
            ->group(function () {
                // Load tenant-specific routes
                require module_path('Tenant', '/Resources/routes/tenant.php');
            });
    }
}