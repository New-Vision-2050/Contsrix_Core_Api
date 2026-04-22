<?php

declare(strict_types=1);

namespace Modules\Shared\University\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Shared\University\Console\FetchUniversitiesCommand;

class UniversityServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'University';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerCommands();
    }

    public function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchUniversitiesCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/universities')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
