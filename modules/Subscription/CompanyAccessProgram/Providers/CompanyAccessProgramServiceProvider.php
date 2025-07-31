<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Subscription\CompanyAccessProgram\Events\CompanyAccessProgramCreated;
use Modules\Subscription\CompanyAccessProgram\Events\CompanyAccessProgramUpdated;
use Modules\Subscription\CompanyAccessProgram\Listeners\CreateMainPackageListener;
use Modules\Subscription\CompanyAccessProgram\Listeners\UpdateMainPackageListener;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\CompanyAccessProgram\Observers\CompanyAccessProgramObserver;

class CompanyAccessProgramServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'CompanyAccessProgram';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerEvents();
        $this->registerObservers();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/company_access_programs')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }

    /**
     * Register model observers
     */
    private function registerObservers(): void
    {
        CompanyAccessProgram::observe(CompanyAccessProgramObserver::class);
    }

    /**
     * Register event listeners
     */
    private function registerEvents(): void
    {
        Event::listen(
            CompanyAccessProgramCreated::class,
            CreateMainPackageListener::class
        );
        
        Event::listen(
            CompanyAccessProgramUpdated::class,
            UpdateMainPackageListener::class
        );
    }
}
