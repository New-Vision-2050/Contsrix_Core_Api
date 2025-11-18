<?php

namespace Modules\WebsiteCMS\WebsiteService\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WebsiteServiceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'middleware' => 'api',
            'prefix' => 'api/v1/website-services',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../Resources/routes/api.php');
        });
    }
}
