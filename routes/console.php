<?php

use App\Console\Commands\CreateWaitingAttendanceCommand;
use App\Console\Commands\UpdateAttendanceStatusCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command(CreateWaitingAttendanceCommand::class)
    ->everyThreeHours()
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance-waiting.log'));

// Update attendance statuses at the end of the workday (7:00 PM)
// This will mark users as absent if they didn't clock in
Schedule::command(UpdateAttendanceStatusCommand::class)
    ->everyThreeHours()
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance-status-update.log'));


// Document notification scheduling
Schedule::command('notifications:send-document-notifications')
    ->dailyAt('09:00')
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/document-notifications.log'));

Schedule::command(CreateWaitingAttendanceCommand::class)
    ->everyThreeHours()
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance-waiting.log'));


Schedule::command('attendance:auto-close-stale-shifts')
    ->everyFiveMinutes()
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance-auto-close-stale-shifts.log'));


Schedule::command('attendance:send-silent-notifications')
    ->everyFiveMinutes()
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance-silent-notifications.log'));
