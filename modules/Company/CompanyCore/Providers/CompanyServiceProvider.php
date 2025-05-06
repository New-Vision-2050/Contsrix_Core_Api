<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Observers\CompanyLegalDataObserver;

class CompanyServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Company';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerSchedules();
        CompanyLegalData::observe(CompanyLegalDataObserver::class);
    }

    public function register(): void
    {
        $this->registerRoutes();
    }
    public function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('companies:delete-inactive')->dailyAt('02:00');
        });
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/companies')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
    public function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Company\CompanyCore\Console\CheckCompanyActivityCommand::class,
            ]);
        }
    }
}
