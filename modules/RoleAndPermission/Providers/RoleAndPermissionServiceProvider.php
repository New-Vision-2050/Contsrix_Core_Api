<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Providers;

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
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerCommands();
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ||  $user->hasRole('admin') ? true : null;
        });
    }

    public function register(): void
    {
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
}
