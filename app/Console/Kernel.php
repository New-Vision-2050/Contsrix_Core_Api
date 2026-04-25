<?php

namespace App\Console;

use App\Console\Commands\CreateHolidayAttendanceCommand;
use App\Console\Commands\CreateWaitingAttendanceCommand;
use App\Console\Commands\UpdateAttendanceStatusCommand;
use App\Console\Commands\SendAttendanceSilentNotificationCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands are auto-discovered by the `commands` method below.
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // Create waiting attendance records early in the morning (5:00 AM)
        $schedule->command(CreateWaitingAttendanceCommand::class)
                ->everyThreeHours()
                ->timezone('Asia/Riyadh')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/attendance-waiting.log'));

        // Update attendance statuses at the end of the workday (7:00 PM)
        // This will mark users as absent if they didn't clock in
        $schedule->command(UpdateAttendanceStatusCommand::class)
            ->everyThreeHours()
            ->timezone('Asia/Riyadh')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/attendance-status-update.log'));

        $schedule->command(SendAttendanceSilentNotificationCommand::class)
            ->everyOddHour()
            ->timezone('Asia/Riyadh')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/attendance-silent-notifications.log'));

        $schedule->command(CreateHolidayAttendanceCommand::class)
            ->dailyAt('00:05')
            ->timezone('Asia/Riyadh')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/attendance-holiday.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
