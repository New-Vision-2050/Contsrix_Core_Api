<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EcoBankAccountServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EcoBankAccount';
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
        Route::prefix('api/v1/ecommerce/bank-accounts')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
