<?php

namespace Modules\Attendance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Attendance\Contracts\TimeConstraintServiceInterface;
use Modules\Attendance\Contracts\LocationConstraintServiceInterface;
use Modules\Attendance\Contracts\DeviceConstraintServiceInterface;
use Modules\Attendance\Contracts\RoleConstraintServiceInterface;
use Modules\Attendance\Contracts\BehavioralConstraintServiceInterface;
use Modules\Attendance\Contracts\SecurityConstraintServiceInterface;
use Modules\Attendance\Contracts\ComplianceConstraintServiceInterface;
use Modules\Attendance\Services\TimeConstraintService;
use Modules\Attendance\Services\LocationConstraintService;
use Modules\Attendance\Services\DeviceConstraintService;
use Modules\Attendance\Services\RoleConstraintService;
use Modules\Attendance\Services\BehavioralConstraintService;
use Modules\Attendance\Services\SecurityConstraintService;
use Modules\Attendance\Services\ComplianceConstraintService;
use Modules\Attendance\Services\AttendanceConstraintService;
use Illuminate\Support\Facades\Event;
use Modules\Attendance\Events\AttendanceConstraintUpdated;
use Modules\Attendance\Events\UpdateAttendance;
use Modules\Attendance\Listeners\HandelAttendanceConstraintUpdate;
use Modules\Attendance\Listeners\LogAttendanceConstraintUpdate;
/**
 * Service Provider for registering all constraint-related services.
 */
class ConstraintServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register specialized constraint service interfaces and implementations
        $this->app->bind(TimeConstraintServiceInterface::class, TimeConstraintService::class);
        $this->app->bind(LocationConstraintServiceInterface::class, LocationConstraintService::class);
        $this->app->bind(DeviceConstraintServiceInterface::class, DeviceConstraintService::class);
        $this->app->bind(RoleConstraintServiceInterface::class, RoleConstraintService::class);
        $this->app->bind(BehavioralConstraintServiceInterface::class, BehavioralConstraintService::class);
        $this->app->bind(SecurityConstraintServiceInterface::class, SecurityConstraintService::class);
        $this->app->bind(ComplianceConstraintServiceInterface::class, ComplianceConstraintService::class);

        // Register services as scoped (cleared per request - Octane compatible)
        // This allows instance-level caching within a single request
        $this->app->scoped(TimeConstraintService::class);
        $this->app->scoped(LocationConstraintService::class);
        $this->app->scoped(DeviceConstraintService::class);
        $this->app->scoped(RoleConstraintService::class);
        $this->app->scoped(BehavioralConstraintService::class);
        $this->app->scoped(SecurityConstraintService::class);
        $this->app->scoped(ComplianceConstraintService::class);
        $this->app->scoped(AttendanceConstraintService::class);
                $this->registerEventListeners();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerEventListeners();
        // Any bootstrapping logic can go here
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            TimeConstraintServiceInterface::class,
            LocationConstraintServiceInterface::class,
            DeviceConstraintServiceInterface::class,
            RoleConstraintServiceInterface::class,
            BehavioralConstraintServiceInterface::class,
            SecurityConstraintServiceInterface::class,
            ComplianceConstraintServiceInterface::class,
            TimeConstraintService::class,
            LocationConstraintService::class,
            DeviceConstraintService::class,
            RoleConstraintService::class,
            BehavioralConstraintService::class,
            SecurityConstraintService::class,
            ComplianceConstraintService::class,
            AttendanceConstraintService::class,
        ];
    }

        protected function registerEventListeners(): void
    {

        Event::listen(
            AttendanceConstraintUpdated::class,
            LogAttendanceConstraintUpdate::class
        );

        Event::listen(
            UpdateAttendance::class,
            HandelAttendanceConstraintUpdate::class
        );
    }
}
