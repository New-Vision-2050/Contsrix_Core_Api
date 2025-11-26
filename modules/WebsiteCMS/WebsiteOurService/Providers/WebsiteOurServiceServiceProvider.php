<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class WebsiteOurServiceServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'WebsiteOurService';
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
        Route::prefix('api/v1/website-our-services')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
