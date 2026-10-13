<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Advance\AdvanceRequest\Services\AdvanceRequestWorkflowNotifier;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Process\Services\WorkflowNotifierRegistry;

class AdvanceRequestServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'AdvanceRequest';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();

        if (! $this->app->bound(WorkflowNotifierRegistry::class)) {
            $this->app->singleton(WorkflowNotifierRegistry::class);
        }
        $this->app->make(WorkflowNotifierRegistry::class)->register(
            ProcedureSettingType::AdvanceRequest->value,
            $this->app->make(AdvanceRequestWorkflowNotifier::class),
        );
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/advance-requests')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

        Route::prefix('api/v1/users')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/user_advance_policies.php');

        Route::prefix('api/v1/my')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/my.php');

        Route::prefix('api/v1/admin/advance-requests')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/admin.php');
    }
}
