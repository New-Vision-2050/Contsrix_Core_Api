<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class BannerServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Banner';
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
        Route::prefix('api/v1/ecommerce/dashboard/banners')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
