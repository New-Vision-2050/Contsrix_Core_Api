<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class LeavePolicyServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'LeavePolicy';
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
        Route::prefix('api/v1/leave-policies')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
