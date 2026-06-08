<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class Shared/ProcessServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Shared/Process';
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
        Route::prefix('api/v1/shared/_processes')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
