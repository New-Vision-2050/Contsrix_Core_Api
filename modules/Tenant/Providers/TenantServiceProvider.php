<?php

namespace Modules\Tenant\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Services\TenantManager;
use Modules\Tenant\Middleware\TenantMiddleware;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerMiddlewares();
        $this->registerConfig();
        $this->registerCommands();
        $this->loadMigrationsFrom(module_path('Tenant', 'Database/Migrations'));
        $this->loadRoutesFrom(module_path('Tenant', 'Resources/routes/api.php'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('tenant.manager', function ($app) {
            return new TenantManager();
        });

        $this->app->alias('tenant.manager', TenantManager::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path('Tenant', 'Config/config.php') => config_path('tenant.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('Tenant', 'Config/config.php'), 'tenant'
        );
    }

    /**
     * Register middlewares.
     */
    protected function registerMiddlewares(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('tenant', TenantMiddleware::class);
    }

    /**
     * Register commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Tenant\Commands\CreateTenantCommand::class,
        ]);
    }
}