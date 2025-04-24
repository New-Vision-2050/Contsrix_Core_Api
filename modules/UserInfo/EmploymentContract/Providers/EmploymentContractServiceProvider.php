<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class EmploymentContractServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'EmploymentContract';
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
        Route::prefix('api/v1/employment_contracts')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
