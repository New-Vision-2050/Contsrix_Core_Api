<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoShopAddressServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoShopAddress';
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
        Route::prefix('api/v1/ecommerce/shop_addresses')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
