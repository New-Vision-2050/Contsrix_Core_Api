<?php

namespace Modules\Attendance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Attendance\Services\AttendanceAnalyticsService;
use Modules\Attendance\Services\MobileAttendanceService;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AttendanceAnalyticsService::class, function ($app) {
            return new AttendanceAnalyticsService();
        });
        
        $this->app->singleton(MobileAttendanceService::class, function ($app) {
            return new MobileAttendanceService(
                $app->make('Modules\Attendance\Services\AttendanceService'),
                $app->make('Modules\Attendance\Services\LocationEnhancementService')
            );
        });
    }
}
