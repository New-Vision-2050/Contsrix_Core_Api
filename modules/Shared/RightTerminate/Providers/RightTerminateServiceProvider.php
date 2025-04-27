<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class RightTerminateServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'RightTerminate';
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
        Route::prefix('api/v1/right_terminates')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
