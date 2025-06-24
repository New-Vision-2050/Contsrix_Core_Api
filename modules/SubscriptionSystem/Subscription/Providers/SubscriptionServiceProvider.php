<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
<<<<<<< HEAD:modules/Subscription/Providers/SubscriptionServiceProvider.php
=======
use Modules\SubscriptionSystem\Subscription\Repositories\FeatureRepository;
use Modules\SubscriptionSystem\Subscription\Services\FeatureCRUDService;
>>>>>>> 955f685f (merge with subscription and solve conflicts):modules/SubscriptionSystem/Subscription/Providers/SubscriptionServiceProvider.php

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
