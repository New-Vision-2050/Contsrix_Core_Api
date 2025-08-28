<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoAddressServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoAddress';
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
        // Assuming lang files are in Modules/Ecommerce/EcoAddress/Resources/lang
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'ecoaddress');
    }
    public function mapRoutes(): void
    {
        Route::prefix('api/v1/ecommerce/addresses')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
