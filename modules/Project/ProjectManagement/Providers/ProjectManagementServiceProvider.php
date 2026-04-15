<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Observers\ProjectManagementObserver;
use Modules\Project\ProjectManagement\Middleware\CheckProjectPermission;

class ProjectManagementServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ProjectManagement';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();
        
        // Register observer
        ProjectManagement::observe(ProjectManagementObserver::class);

        // Register middleware
        $this->app['router']->aliasMiddleware('project.permission', CheckProjectPermission::class);
    }

    public function register(): void
    {
        $this->registerConfig(); // Load config before routes
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/projects')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
