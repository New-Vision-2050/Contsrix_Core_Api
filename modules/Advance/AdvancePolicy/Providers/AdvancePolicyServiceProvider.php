<?php

declare(strict_types=1);

namespace Modules\Advance\AdvancePolicy\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;

class AdvancePolicyServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'AdvancePolicy';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/advance-policies')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }
}
