<?php

declare(strict_types=1);

namespace Modules\Reports\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class ReportsServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Reports';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();
        $this->registerViews();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    protected function registerViews(): void
    {
        $alias      = 'reports';
        $sourcePath = $this->getModulePath() . '/Resources/views';
        $viewPath   = resource_path('views/modules/' . $alias);

        $this->publishes([$sourcePath => $viewPath], 'views');
        $this->loadViewsFrom([$viewPath, $sourcePath], $alias);
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/reports')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }
}
