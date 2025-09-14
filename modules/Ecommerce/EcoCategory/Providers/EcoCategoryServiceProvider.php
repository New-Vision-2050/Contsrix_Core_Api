<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoCategoryServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoCategory';
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
    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'ecocategory');
    }
    public function mapRoutes(): void
    {
        Route::prefix('api/v1/ecommerce/categories')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
