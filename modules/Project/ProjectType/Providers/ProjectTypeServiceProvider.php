<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
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
        
        // Register observer
        ProjectType::observe(ProjectTypeObserver::class);
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
            ->group($this->getModulePath() . '/Resources/routes/project-sharing.php');
    }
}
