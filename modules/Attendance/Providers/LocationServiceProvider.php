<?php

namespace Modules\Attendance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Attendance\Services\LocationEnhancementService;
use Modules\Attendance\Services\LocationTrackingService;
use Modules\Company\CompanyCore\Services\CompanyProfileService;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LocationEnhancementService::class, function ($app) {
            return new LocationEnhancementService(
                $app->make(CompanyProfileService::class),
                $app->make(LocationTrackingService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // No boot logic needed
    }
}
