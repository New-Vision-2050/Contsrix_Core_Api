<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class CompanyRegistrationFormServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'CompanyRegistrationForm';
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
        Route::prefix('api/v1/company_registration_forms')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
