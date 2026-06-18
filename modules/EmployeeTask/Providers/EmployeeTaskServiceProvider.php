<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\EmployeeTask\Services\EmployeeTaskApprovalService;
use Modules\EmployeeTask\Services\EmployeeTaskAutoCloseService;
use Modules\EmployeeTask\Services\EmployeeTaskEndRequestService;
use Modules\EmployeeTask\Services\EmployeeTaskFormConditionService;
use Modules\EmployeeTask\Services\EmployeeTaskAvailableActionsService;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionService;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionWorkflowService;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\EmployeeTask\Services\EmployeeTaskLocationService;
use Modules\EmployeeTask\Services\EmployeeTaskReportService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\EmployeeTask\Services\EmployeeTaskWorkflowNotifier;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Process\Services\WorkflowNotifierRegistry;

class EmployeeTaskServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'EmployeeTask';

    protected string $moduleNameLower = 'employeetask';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        if (! $this->app->bound(WorkflowNotifierRegistry::class)) {
            $this->app->singleton(WorkflowNotifierRegistry::class);
        }
        $this->app->make(WorkflowNotifierRegistry::class)->register(
            ProcedureSettingType::EmployeeTask->value,
            $this->app->make(EmployeeTaskWorkflowNotifier::class),
        );
    }

    public function register(): void
    {
        $this->app->register(EmployeeTaskRouteServiceProvider::class);

        $this->app->singleton(EmployeeTaskRequestService::class);
        $this->app->singleton(EmployeeTaskLifecycleService::class);
        $this->app->singleton(EmployeeTaskExtensionService::class);
        $this->app->singleton(EmployeeTaskExtensionWorkflowService::class);
        $this->app->singleton(EmployeeTaskAutoCloseService::class);
        $this->app->singleton(EmployeeTaskLocationService::class);
        $this->app->singleton(EmployeeTaskReportService::class);
        $this->app->singleton(EmployeeTaskApprovalService::class);
        $this->app->singleton(EmployeeTaskEndRequestService::class);
        $this->app->singleton(EmployeeTaskFormConditionService::class);
        $this->app->singleton(EmployeeTaskAvailableActionsService::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/permissions.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower.'.permissions');
        }
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Lang'), $this->moduleNameLower);
        }
    }
}
