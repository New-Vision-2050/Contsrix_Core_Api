<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class TypeWorkingHourServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'TypeWorkingHour';
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
        Route::prefix('api/v1/type_working_hours')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
