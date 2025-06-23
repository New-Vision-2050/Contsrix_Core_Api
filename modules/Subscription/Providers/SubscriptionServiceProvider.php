<?php

declare(strict_types=1);

namespace Modules\Subscription\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Subscription\Repositories\FeatureRepository;
use Modules\Subscription\Services\FeatureCRUDService;

class SubscriptionServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Subscription';
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
        Route::prefix('api/v1/subscriptions')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
