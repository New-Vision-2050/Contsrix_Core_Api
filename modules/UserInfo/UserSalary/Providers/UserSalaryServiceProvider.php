<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class UserSalaryServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'UserSalary';
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
        Route::prefix('api/v1/user_salaries')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
