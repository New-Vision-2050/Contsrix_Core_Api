<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Providers;

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\RoleAndPermission\Commands\SyncCompanyPermissionsCommand;

class RoleAndPermissionServiceProvider extends ModuleServiceProvider
{
    /**
     * @var string[]
     */
    protected $commands = [
        SyncCompanyPermissionsCommand::class,
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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ||  $user->hasRole('admin' ) || (auth()->check() && auth()->user()->is_owner == 1) ? true : null;
        });

        IlluminateRoute::macro('permission', function (...$permissions) {
            if(auth()->check() && auth()->user()->is_owner == 1) {
                return $this;
            }
            $permissions = collect($permissions)
                ->flatten()
                ->map(fn ($permission) => $permission instanceof \UnitEnum ? $permission->value : $permission)
                ->all();


            return $this->middleware("permission:" . implode('|', $permissions));
        });
    }

    public function register(): void
    {
        $this->registerConfig(); // Moved here to load before routes
        $this->registerRoutes();
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

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            $this->getModulePath() . '/Config/permissions.php', 'permissions'
        );
    }
}
