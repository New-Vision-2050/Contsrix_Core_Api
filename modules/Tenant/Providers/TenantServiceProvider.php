<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Events\UserCreated;
use Modules\Tenant\Listeners\SendTenantWelcomeEmail;
use Modules\Tenant\Middleware\VerifyTenantToken;
use Modules\Tenant\Observers\CompanyObserver;
use Modules\Tenant\Services\TenantAuthService;
use Modules\Tenant\Services\TenantReportingService;
use Modules\Tenant\Services\TenantService;
use Modules\Tenant\Services\TenantWelcomeService;
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
        $this->registerEvents();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        
        // Register services as singletons
        $this->app->singleton(TenantService::class, function ($app) {
            return new TenantService();
        });
        
        $this->app->singleton(TenantAuthService::class, function ($app) {
            return new TenantAuthService();
        });
        
        $this->app->singleton(TenantReportingService::class, function ($app) {
            return new TenantReportingService($app->make(TenantService::class));
        });
        
        $this->app->singleton(TenantWelcomeService::class, function ($app) {
            return new TenantWelcomeService($app->make(TenantService::class));
        });
        
        // Register commands
        $this->commands([
            \Modules\Tenant\Commands\SetCompanyUserPasswordCommand::class,
            \Modules\Tenant\Commands\SendTenantWelcomeEmailsCommand::class,
            \Modules\Tenant\Commands\CreateTestTenantCommand::class,
            \Modules\Tenant\Commands\AddUserToTenant::class,
        ]);
    }
    
    /**
     * Register events.
     *
     * @return void
     */
    protected function registerEvents()
    {
        Event::listen(
            UserCreated::class,
            SendTenantWelcomeEmail::class
        );
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
        $router->aliasMiddleware('tenant.auth', VerifyTenantToken::class);
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
