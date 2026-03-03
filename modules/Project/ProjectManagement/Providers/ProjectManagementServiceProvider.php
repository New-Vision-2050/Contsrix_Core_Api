<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Observers\ProjectManagementObserver;

class ProjectManagementServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ProjectManagement';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        
        // Register observer
        ProjectManagement::observe(ProjectManagementObserver::class);
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/projects')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
