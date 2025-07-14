<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Modules\Attendance\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AuditAbsencesCommand extends Command
{
    protected $signature = 'attendance:audit-absences';
    protected $description = 'Periodically audits all constraints to find and mark absent employees.';

    public function __construct(
        private AttendanceService $attendanceService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting periodic absence audit...');

        // 1. Get all active constraints that have time rules, along with the branches they apply to.
        $constraints = AttendanceConstraint::with('branches.users') // Eager load for performance
            ->where('is_active', true)
            ->whereJsonContainsKey('constraint_config->time_rules')
            ->get();

        foreach ($constraints as $constraint) {
            $this->processConstraint($constraint);
        }

        $this->info('Absence audit completed.');
        return Command::SUCCESS;
    }

    /**
     * Process a single constraint to find and mark absent users.
     */
    private function processConstraint(AttendanceConstraint $constraint)
    {
        $this->line("Processing Constraint: '{$constraint->constraint_name}' (ID: {$constraint->id})");

        $timeRules = $constraint->constraint_config['time_rules'] ?? null;
        if (!$timeRules) {
            return; // Skip if no time rules
        }

        // Determine if "now" is a working time according to this constraint.
        $now = Carbon::now();
        $isWorkTimeResult = $this->isCurrentlyWorkTime($timeRules, $now);

        if (!$isWorkTimeResult['is_work_time']) {
            $this->line(" -> Not currently a work time for this constraint. Skipping.");
            return;
        }

        $this->info(" -> It is currently a work time. Checking employees...");

        // Get all users this constraint applies to.
        $users = $this->getUsersForConstraint($constraint);

        foreach ($users as $user) {
            try {
                // Check if the user already has an attendance record for today.
                $todaysAttendance = $this->attendanceService->getAttendanceForUserOnDate($user, $now->copy()->startOfDay());

                if (!$todaysAttendance) {
                    $this->warn("   - User {$user->name} is ABSENT. Creating absence record.");
                    $this->attendanceService->createAbsenceRecord(
                        $user,
                        $now->copy()->startOfDay(),
                        "Absent: No clock-in detected during scheduled shift."
                    );
                }
            } catch (\Exception $e) {
                Log::error("Failed during absence check for user {$user->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Determines if the current moment is a working time based on a time rule config.
     */
    private function isCurrentlyWorkTime(array $timeRules, Carbon $now): array
    {
        $dayOfWeek = strtolower($now->format('l'));
        $weeklySchedule = $timeRules['weekly_schedule'] ?? [];
        $holidays = $timeRules['holidays'] ?? [];

        // Check if today is a holiday
        if (collect($holidays)->contains(fn($h) => $now->isSameDay($h['date'] ?? null))) {
            return ['is_work_time' => false, 'reason' => 'Holiday'];
        }

        // Check if today is a scheduled workday
        $daySchedule = $weeklySchedule[$dayOfWeek] ?? null;
        if (!$daySchedule || !($daySchedule['enabled'] ?? false)) {
            return ['is_work_time' => false, 'reason' => 'Day Off / Weekend'];
        }

        // Check if current time is within any work period
        $currentTimeStr = $now->format('H:i');
        foreach (($daySchedule['periods'] ?? []) as $period) {
            if ($currentTimeStr >= $period['start_time'] && $currentTimeStr <= $period['end_time']) {
                return ['is_work_time' => true, 'reason' => 'Within work period'];
            }
        }

        return ['is_work_time' => false, 'reason' => 'Outside work periods'];
    }

    /**
     * Gets all users that a specific constraint applies to.
     */
    private function getUsersForConstraint(AttendanceConstraint $constraint)
    {
        // If the constraint is tied to a specific user, just return that user.
        if ($constraint->user_id) {
            return User::where('id', $constraint->user_id)->get();
        }

        // If the constraint is tied to specific branches, get all users from those branches.
        if (!empty($constraint->branch_ids)) {
            // This assumes a user is directly linked to a branch via `management_hierarchy_id`.
            return User::whereIn('management_hierarchy_id', $constraint->branch_ids)->get();
        }

        // If it's a global constraint, get all users in the company.
        return User::where('company_id', $constraint->company_id)->get();
    }
}
