<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Company\ManagementHierarchy\Events\CompanyCreatedEvent;
use Modules\Company\ManagementHierarchy\Listeners\CreateHierarchyListener;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\Branch;
use Modules\Company\ManagementHierarchy\Models\Management;
use Modules\Company\ManagementHierarchy\Observers\BranchObserver;
use Modules\Company\ManagementHierarchy\Observers\ManagementObserver;

class ManagementHierarchyServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ManagementHierarchy';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();

        Event::listen(CompanyCreatedEvent::class,CreateHierarchyListener::class );
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/management_hierarchies')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
