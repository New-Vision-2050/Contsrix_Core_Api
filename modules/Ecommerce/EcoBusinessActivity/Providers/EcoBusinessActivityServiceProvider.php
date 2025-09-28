<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoBusinessActivityServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoBusinessActivity';
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
        Route::prefix('api/v1/ecommerce/dashboard/business-activities')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/dashboard.php');
    }
}
