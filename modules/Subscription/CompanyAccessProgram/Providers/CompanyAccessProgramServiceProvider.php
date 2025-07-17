<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class CompanyAccessProgramServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'CompanyAccessProgram';
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
        Route::prefix('api/v1/company_access_programs')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
