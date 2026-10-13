<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Leave\LeaveRequest\Services\LeaveRequestWorkflowNotifier;
use Modules\Leave\LeaveRequest\Services\UserLeavePolicyService;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Process\Services\WorkflowNotifierRegistry;
use Modules\User\Models\User;

class LeaveRequestServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'LeaveRequest';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();

        if (! $this->app->bound(WorkflowNotifierRegistry::class)) {
            $this->app->singleton(WorkflowNotifierRegistry::class);
        }
        $this->app->make(WorkflowNotifierRegistry::class)->register(
            ProcedureSettingType::LeaveRequest->value,
            $this->app->make(LeaveRequestWorkflowNotifier::class),
        );

        // Every new user follows the company default leave policy (21-day
        // "Annual Year"). Never block user creation if the assignment fails.
        User::created(function (User $user): void {
            if (! tenancy()->initialized || empty(tenant('id'))) {
                return;
            }

            try {
                $this->app->make(UserLeavePolicyService::class)
                    ->assignDefaultPolicyToUser((string) $user->id, (string) tenant('id'));
            } catch (\Throwable $exception) {
                Log::warning('Default leave policy auto-assign failed', [
                    'user_id' => (string) $user->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/leave-requests')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

        Route::prefix('api/v1/users')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/user_leave_policies.php');

        Route::prefix('api/v1/my')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/my.php');

        Route::prefix('api/v1/admin/leave-requests')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/admin.php');
    }
}
