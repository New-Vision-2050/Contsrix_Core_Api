<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoReportServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoReport';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/ecommerce')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/dashboard.php');

    }
}
