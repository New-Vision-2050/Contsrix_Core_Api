<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class ResourceShareServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ResourceShare';
    }

    public function boot(): void
    {
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/resource-shares')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }
}
