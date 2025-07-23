<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Modules\Attendance\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateAttendanceStatusCommand extends Command
{
    protected $signature = 'attendance:update-status {--date= : Optional date in Y-m-d format to process (defaults to today)}';
    protected $description = 'Updates attendance statuses from "Waiting" to "Present" or "Absent" based on clock-in records';

    private AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        parent::__construct();
        $this->attendanceService = $attendanceService;
    }

    public function handle()
    {
        $this->info('Starting attendance status update process...');
        $timeNow = Carbon::now();
        
        $waitingAttendances = Attendance::where('status', Attendance::STATUS_WAITING)
            ->where('end_time', '<', $timeNow)
            ->get();
        $this->info('Found ' . count($waitingAttendances) . ' waiting attendances.');    
        foreach ($waitingAttendances as $item) {
            $this->attendanceService->updateAttendanceStatus($item, Attendance::STATUS_COMPLETED,true);
        }

        $this->info('Attendance status update completed successfully.');
        return Command::SUCCESS;
    }
}
