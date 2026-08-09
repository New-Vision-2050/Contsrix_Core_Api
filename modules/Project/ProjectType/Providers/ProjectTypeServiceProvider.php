<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Console\SyncSafetyRecordsFromNotificationsCommand;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Project\ProjectType\Observers\ProjectTypeObserver;

class ProjectTypeServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ProjectType';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerViews();

        // Register observer
        ProjectType::observe(ProjectTypeObserver::class);

        Relation::morphMap([
            'project_notification' => ProjectNotification::class,
            'project_order_permit' => ProjectOrderPermit::class,
        ]);
    }

    protected function registerViews(): void
    {
        $alias = 'project-type';
        $sourcePath = $this->getModulePath('Resources/views');
        $viewPath = resource_path('views/modules/'.$alias);

        $this->publishes([$sourcePath => $viewPath], 'views');
        $this->loadViewsFrom([$viewPath, $sourcePath], $alias);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncSafetyRecordsFromNotificationsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/project-types')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

        Route::prefix('api/v1')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/order-permit.php');
    }
}
