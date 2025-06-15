<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class AcademicSpecializationServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'AcademicSpecialization';
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
        Route::prefix('api/v1/academic_specializations')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
