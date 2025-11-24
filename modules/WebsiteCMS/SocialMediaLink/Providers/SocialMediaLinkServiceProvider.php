<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;

class SocialMediaLinkServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'SocialMediaLink';
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
        Route::prefix('api/v1/social-media-links')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
