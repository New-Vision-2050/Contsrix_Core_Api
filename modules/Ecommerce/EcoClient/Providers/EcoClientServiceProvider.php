<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoClientServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoClient';
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
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'ecoclient');
    }
    public function mapRoutes(): void
    {
        Route::prefix('api/v1/ecommerce/clients')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
