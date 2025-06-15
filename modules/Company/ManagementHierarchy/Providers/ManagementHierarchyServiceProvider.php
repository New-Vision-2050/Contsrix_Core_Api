<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Company\ManagementHierarchy\Events\CompanyCreatedEvent;
use Modules\Company\ManagementHierarchy\Listeners\CreateHierarchyListener;
<<<<<<< HEAD
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Observers\ManagementHierarchyObserver;
use Modules\Company\ManagementHierarchy\Observers\UserCountObserver;
use Modules\User\Models\User;
=======
>>>>>>> 7be6c72c (merge with stage (first version ))

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
<<<<<<< HEAD
        ManagementHierarchy::observe(ManagementHierarchyObserver::class);
        User::observe(UserCountObserver::class);
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
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
