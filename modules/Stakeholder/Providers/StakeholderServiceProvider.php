<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class StakeholderServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Stakeholder';
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
        Route::prefix('api/v1/stakeholders')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }
}
