<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Providers;

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\RoleAndPermission\Commands\SyncCompanyPermissionsCommand;
use Modules\RoleAndPermission\Console\Commands\SyncPermissionsCommand;
use Modules\RoleAndPermission\Console\Commands\PermissionCacheCommand;
use Modules\RoleAndPermission\Middleware\AdvancedPermissionMiddleware;
use Modules\RoleAndPermission\Services\PermissionService;
use Modules\RoleAndPermission\Services\PermissionAuditService;

class RoleAndPermissionServiceProvider extends ModuleServiceProvider
{
    /**
     * @var string[]
     */
    protected $commands = [
        SyncCompanyPermissionsCommand::class,
        SyncPermissionsCommand::class,
        PermissionCacheCommand::class,
    ];

    public static function getModuleName(): string
    {
        return 'RoleAndPermission';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerMiddleware();
        
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ||  $user->hasRole('admin') ? true : null;
        });

        IlluminateRoute::macro('permission', function (...$permissions) {
            $permissions = collect($permissions)
                ->flatten()
                ->map(fn ($permission) => $permission instanceof \UnitEnum ? $permission->value : $permission)
                ->all();

            return $this->middleware("permission:" . implode('|', $permissions));
        });

        // Register advanced permission macro
        IlluminateRoute::macro('advancedPermission', function (...$permissions) {
            $permissions = collect($permissions)
                ->flatten()
                ->map(fn ($permission) => $permission instanceof \UnitEnum ? $permission->value : $permission)
                ->all();

            return $this->middleware("advanced.permission:" . implode('|', $permissions));
        });
    }

    public function register(): void
    {
        $this->registerConfig(); // Moved here to load before routes
        $this->registerRoutes();
        $this->registerServices();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/role_and_permissions')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * Register middleware
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('advanced.permission', AdvancedPermissionMiddleware::class);
    }

    /**
     * Register services
     */
    protected function registerServices(): void
    {
        $this->app->singleton(PermissionService::class, function ($app) {
            return new PermissionService();
        });

        $this->app->singleton(PermissionAuditService::class, function ($app) {
            return new PermissionAuditService();
        });
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            $this->getModulePath() . '/Config/permissions.php', 'permissions'
        );
    }
}
