<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\WebsiteCMS\WebsiteSetting\Services\WebsiteSettingUploadService;
use Modules\Shared\Media\Services\FileUploadService;

class WebsiteSettingServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'WebsiteSetting';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();
    }

    public function register(): void
    {
        $this->registerRoutes();
        $this->registerServices();
    }

    protected function registerServices(): void
    {
        $this->app->singleton(WebsiteSettingUploadService::class, function ($app) {
            return new WebsiteSettingUploadService($app->make(FileUploadService::class));
        });
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/website_settings')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }
}
