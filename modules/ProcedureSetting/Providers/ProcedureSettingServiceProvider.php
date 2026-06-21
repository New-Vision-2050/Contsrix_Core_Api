<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\ProcedureSetting\Events\WorkflowProcedureTaken;
use Modules\ProcedureSetting\Events\WorkflowStepActivated;
use Modules\ProcedureSetting\Listeners\RecordInternalProcedureTaken;
use Modules\ProcedureSetting\Listeners\SendWorkflowStepNotification;

class ProcedureSettingServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ProcedureSetting';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerEventListeners();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            WorkflowStepActivated::class,
            SendWorkflowStepNotification::class,
        );

        Event::listen(
            WorkflowProcedureTaken::class,
            RecordInternalProcedureTaken::class,
        );
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/procedure-settings')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}
