<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class FlashDealServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'FlashDeal';
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
        Route::prefix('api/v1/ecommerce/dashboard/flash_deals')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

        Route::prefix('api/v1/ecommerce/website/flash_deals')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/website.php');
    }
}
