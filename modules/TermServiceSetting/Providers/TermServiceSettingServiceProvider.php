<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class TermServiceSettingServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'TermServiceSetting';
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
        Route::prefix('api/v1/term-service-settings')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
