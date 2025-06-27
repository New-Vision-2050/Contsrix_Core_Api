<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider as BasePackageModuleServiceProvider;

class ModuleServiceProvider extends BasePackageModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Module';
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
        Route::prefix('api/v1/modules')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
