<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\ClientRequest\Models\ClientRequest;
use Modules\ClientRequest\Observers\ClientRequestObserver;

class ClientRequestServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ClientRequest';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        
        // Register observer
        ClientRequest::observe(ClientRequestObserver::class);
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/client-requests')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }
}
