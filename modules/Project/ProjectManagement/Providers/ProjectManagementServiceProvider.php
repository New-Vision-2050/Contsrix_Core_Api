<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Modules\Project\ProjectManagement\Observers\ProjectManagementObserver;
use Modules\Project\ProjectManagement\Observers\ProjectRoleObserver;
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
        $this->registerViews();
        
        // Register observers
        ProjectManagement::observe(ProjectManagementObserver::class);
        ProjectRole::observe(ProjectRoleObserver::class);

        // Register middleware
        $this->app['router']->aliasMiddleware('project.permission', CheckProjectPermission::class);
    }

    /**
     * Register views
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . strtolower($this->getModuleName()));
        $sourcePath = $this->getModulePath() . '/Resources/views';

        $this->loadViewsFrom(array_merge([$sourcePath], [$viewPath]), 'project-management');
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
