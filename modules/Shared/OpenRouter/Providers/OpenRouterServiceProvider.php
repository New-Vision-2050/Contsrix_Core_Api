<?php

namespace Modules\Shared\OpenRouter\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\OpenRouter\Services\OpenRouterGeoService;

class OpenRouterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OpenRouterGeoService::class, function ($app) {
            return new OpenRouterGeoService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Add any boot method functionality if needed
    }
}
