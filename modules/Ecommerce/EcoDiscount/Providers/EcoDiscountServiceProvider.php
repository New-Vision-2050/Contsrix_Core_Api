<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoDiscountServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoDiscount';
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
        Route::prefix('api/v1/ecommerce/discounts')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
