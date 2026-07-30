<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\PCloud\Services\PCloudArchiveSyncService;
use Modules\Shared\PCloud\Services\PCloudClient;

class PCloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pcloud.php', 'pcloud');

        $this->app->singleton(PCloudClient::class, function () {
            return new PCloudClient();
        });

        $this->app->singleton(PCloudArchiveSyncService::class, function ($app) {
            return new PCloudArchiveSyncService($app->make(PCloudClient::class));
        });
    }

    public function boot(): void
    {
        //
    }
}
