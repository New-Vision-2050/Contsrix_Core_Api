<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Observers\CompanyObserver;
use Modules\Tenant\Services\TenantService;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Tenant';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'tenant';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerMiddleware();
        $this->registerObservers();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        
        // Register the TenantService as a singleton
        $this->app->singleton(TenantService::class, function ($app) {
            return new TenantService();
        });
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register middleware.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        // Register tenancy middleware in the router
        $router = $this->app['router'];
        
        // Add middleware aliases
        $router->aliasMiddleware('tenancy', InitializeTenancyByDomain::class);
        $router->aliasMiddleware('prevent-access-from-central-domains', PreventAccessFromCentralDomains::class);
    }
    
    /**
     * Register observers.
     *
     * @return void
     */
    protected function registerObservers()
    {
        // Register the CompanyObserver to automatically create tenants when companies are created
        Company::observe(CompanyObserver::class);
    }
}
