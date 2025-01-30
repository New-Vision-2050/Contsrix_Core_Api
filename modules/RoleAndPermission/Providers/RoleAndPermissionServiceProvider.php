<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class RoleAndPermissionServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'RoleAndPermission';
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
        Route::prefix('api/v1/role_and_permissions')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
