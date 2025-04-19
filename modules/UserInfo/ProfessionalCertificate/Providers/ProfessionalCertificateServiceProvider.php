<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class ProfessionalCertificateServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ProfessionalCertificate';
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
        Route::prefix('api/v1/professional_certificates')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
