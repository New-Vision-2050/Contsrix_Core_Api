<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class UserRelativeServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'UserRelative';
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
        Route::prefix('api/v1/user_relatives')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
