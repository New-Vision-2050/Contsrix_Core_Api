<?php

declare(strict_types=1);

namespace Modules\Attendance\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Attendance\Domain\Breaks\AutoBreakComputer;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\EarlyDeparturePolicy;
use Modules\Attendance\Domain\Calculator\LatenessPolicy;
use Modules\Attendance\Domain\Calculator\OvertimePolicy;
use Modules\Attendance\Domain\Calculator\StandardEarlyDeparturePolicy;
use Modules\Attendance\Domain\Calculator\StandardLatenessPolicy;
use Modules\Attendance\Domain\Calculator\StandardOvertimePolicy;
use Modules\Attendance\Domain\Time\Clock;
use Modules\Attendance\Domain\Time\SystemClock;
use Modules\Attendance\Domain\Time\TimezoneResolver;
use Modules\Attendance\Events\AttendanceClockedIn;
use Modules\Attendance\Listeners\HandleAttendanceLateness;
use Modules\Attendance\Services\AutoCloseAttendanceService;
use Modules\Attendance\Services\ClockInService;
use Modules\Attendance\Services\ClockOutService;

class AttendanceServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Attendance';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'attendance';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerSchedules();
        $this->registerEventListeners();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(ConstraintServiceProvider::class);

        // Domain layer — all singletons; stateless, Octane-safe.
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(TimezoneResolver::class);
        $this->app->singleton(AutoBreakComputer::class);

        $this->app->singleton(LatenessPolicy::class, StandardLatenessPolicy::class);
        $this->app->singleton(OvertimePolicy::class, StandardOvertimePolicy::class);
        $this->app->singleton(EarlyDeparturePolicy::class, StandardEarlyDeparturePolicy::class);

        $this->app->singleton(AttendanceCalculator::class, function ($app) {
            return new AttendanceCalculator(
                $app->make(LatenessPolicy::class),
                $app->make(OvertimePolicy::class),
                $app->make(EarlyDeparturePolicy::class),
            );
        });

        // Application services — stateless, Octane-safe.
        $this->app->singleton(AutoCloseAttendanceService::class);
        $this->app->singleton(ClockInService::class);
        $this->app->singleton(ClockOutService::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
    public function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('attendance:create-waiting')->everySixHours();
            // $schedule->command('attendance:update-status')->everyThreeHours();
            $schedule->command('attendance:create-holiday-attendance')
                ->dailyAt('00:05')
                ->timezone('Asia/Riyadh')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/attendance-holiday.log'));
        });
    }
    protected function registerEventListeners(): void
    {
        Event::listen(
            AttendanceClockedIn::class,
            HandleAttendanceLateness::class
        );
    }
}
