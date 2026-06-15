<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;

class InternalProcessTypeServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'InternalProcessType';
    }

    public function boot(): void
    {
        $this->registerMigrations();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }
}
