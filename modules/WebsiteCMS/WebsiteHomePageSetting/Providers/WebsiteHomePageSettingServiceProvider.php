<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Models\WebsiteHomePageSetting;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Observers\WebsiteHomePageSettingObserver;

class WebsiteHomePageSettingServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'WebsiteHomePageSetting';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerObservers();
    }

    private function registerObservers(): void
    {
        WebsiteHomePageSetting::observe(WebsiteHomePageSettingObserver::class);
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/website-home-page-settings')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
