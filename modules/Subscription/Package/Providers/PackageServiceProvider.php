<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\Observers\PackageObserver;
use Modules\Subscription\Package\Commands\AssignAllPermissionsToMainPackageCommand;

class PackageServiceProvider extends ModuleServiceProvider
{
    /**
     * @var string[]
     */
    protected array $commands = [
        AssignAllPermissionsToMainPackageCommand::class,
    ];

    public static function getModuleName(): string
    {
        return 'Package';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerObservers();
        $this->registerCommands();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/packages')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }

    /**
     * Register model observers
     */
    private function registerObservers(): void
    {
        Package::observe(PackageObserver::class);
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
