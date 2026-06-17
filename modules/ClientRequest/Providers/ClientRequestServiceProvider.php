<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\ClientRequest\Models\ClientRequest;
use Modules\ClientRequest\Observers\ClientRequestObserver;
use Modules\ClientRequest\Services\ClientRequestWorkflowNotifier;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Process\Services\WorkflowNotifierRegistry;

class ClientRequestServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ClientRequest';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        // $this->registerConfig();
        $this->registerMigrations();

        // Register observer
        ClientRequest::observe(ClientRequestObserver::class);
        if (! $this->app->bound(WorkflowNotifierRegistry::class)) {
            $this->app->singleton(WorkflowNotifierRegistry::class);
        }
        $this->app->make(WorkflowNotifierRegistry::class)->register(
            ProcedureSettingType::ClientRequest->value,
            $this->app->make(ClientRequestWorkflowNotifier::class),
        );
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/client-requests')
            ->middleware('api')
            ->group($this->getModulePath().'/Resources/routes/api.php');
    }
}
