<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class WebsiteIconServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'WebsiteIcon';
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
        Route::prefix('api/v1/website-icons')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
