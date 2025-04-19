<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class BankAccountServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'BankAccount';
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
        Route::prefix('api/v1/bank_accounts')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
