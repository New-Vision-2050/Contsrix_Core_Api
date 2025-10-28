<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class PaymentMethodServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'PaymentMethod';
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
        Route::prefix('api/v1/ecommerce/dashboard/payment_methods')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
