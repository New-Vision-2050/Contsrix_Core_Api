<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoBrandServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoBrand';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
    }
    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'ecobrand');
    }
    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/ecommerce/dashboard/brands')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/dashboard.php');

    }
}
