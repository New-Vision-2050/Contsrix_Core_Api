<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Leave\PublicHoliday\Commands\SeedHolidaysCommand;
use Modules\Leave\PublicHoliday\Commands\TestApiCommand;

class PublicHolidayServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'PublicHoliday';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerCommands();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/public-holidays')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }

    /**
     * Register console commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedHolidaysCommand::class,
                TestApiCommand::class,
            ]);
        }
    }
}
