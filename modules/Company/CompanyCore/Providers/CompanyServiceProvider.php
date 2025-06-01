<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Events\CompanyLegalDataCreated;
use Modules\Company\CompanyCore\Events\CompanyLegalDataUpdated;
use Modules\Company\CompanyCore\Events\CompanyLegalDataDeleted;
use Modules\Company\CompanyCore\Listeners\CompanyDataChangeSubscriber;
use Modules\Company\CompanyCore\Listeners\CreateOfficialDocumentFromLegalData;
use Modules\Company\CompanyCore\Listeners\UpdateOfficialDocumentFromLegalData;
use Modules\Company\CompanyCore\Listeners\DeleteOfficialDocumentFromLegalData;
use Modules\Company\CompanyCore\Observers\CompanyObserver;

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
        $this->registerEventListeners();
        Company::observe(CompanyObserver::class);

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

    /**
     * Register event listeners.
     *
     * @return void
     */
    protected function registerEventListeners(): void
    {
        Event::listen(
            CompanyLegalDataCreated::class,
            CreateOfficialDocumentFromLegalData::class
        );

        Event::listen(
            CompanyLegalDataUpdated::class,
            UpdateOfficialDocumentFromLegalData::class
        );

        Event::listen(
            CompanyLegalDataDeleted::class,
            DeleteOfficialDocumentFromLegalData::class
        );
        
        // Register the subscriber that handles company data change events for cache clearing
        Event::subscribe(CompanyDataChangeSubscriber::class);
    }
}
