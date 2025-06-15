<?php

namespace Modules\PageBuilder\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\PageBuilder\Services\Contracts\SchemaServiceInterface;
use Modules\PageBuilder\Services\SchemaService;

class PageBuilderServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'PageBuilder';
    protected string $moduleNameLower = 'pagebuilder';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        
        // Bind SchemaService to its interface
        $this->app->bind(SchemaServiceInterface::class, SchemaService::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }
}
