<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Attendance\Models\Attendance;
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

        // Allow running the command for a specific historical date for testing.
        $dateToCheck = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $this->info("Auditing absences for date: " . $dateToCheck->toDateString());

        // 1. Get all active constraints that contain time-based rules.
        $constraints = AttendanceConstraint::where('is_active', true)
            ->whereJsonContainsKey('constraint_config->time_rules')
            ->get();

        if ($constraints->isEmpty()) {
            $this->warn('No active time-based constraints found. Exiting.');
            return Command::SUCCESS;
        }

        // Process each constraint to find the employees it applies to.
        foreach ($constraints as $constraint) {
            $this->processConstraint($constraint, $dateToCheck);
        }

        $this->info('Absence audit completed successfully.');
        return Command::SUCCESS;
    }

    /**
     * Process a single constraint to find and mark absent users for a given date.
     */
    private function processConstraint(AttendanceConstraint $constraint, Carbon $date): void
    {
        $this->line("--> Processing Constraint: '{$constraint->constraint_name}'");

        // 2. Check if the audit date was a scheduled workday according to this constraint.
        if (!$this->isWorkDay($constraint->constraint_config['time_rules'] ?? [], $date)) {
            $this->line("    -> Not a scheduled work day for this constraint. Skipping.");
            return;
        }

        $this->info("    -> Was a scheduled work day. Checking employees...");

        // 3. Get the QUERY BUILDER for users this constraint applies to.
        //    This is a performance optimization - we don't fetch all users at once.
        $usersQuery = $this->getUsersQueryForConstraint($constraint);

        // 4. Process users in manageable chunks to keep memory usage low.
        $usersQuery->chunkById(200, function ($users) use ($date) {
            if ($users->isEmpty()) {
                return;
            }

            // Get all user IDs from the current chunk.
            $userIdsInChunk = $users->pluck('id')->all();

            // 5. In ONE database query, find which of these users already have an attendance record today.
            $presentUserIds = $this->attendanceService->getPresentUserIdsOnDate($userIdsInChunk, $date);

            // 6. Determine who is absent by finding the difference.
            $absentUserIds = array_diff($userIdsInChunk, $presentUserIds);

            if (empty($absentUserIds)) {
                $this->info("    -> All " . count($users) . " users in this chunk were present or already marked.");
                return; // Continue to the next chunk
            }

            $this->warn("    -> Found " . count($absentUserIds) . " absent users in this chunk. Creating records...");

            // 7. Loop through ONLY the absent users to create records.
            foreach ($users->whereIn('id', $absentUserIds) as $absentUser) {
                try {
                    $this->attendanceService->createAbsenceRecord(
                        $absentUser,
                        $date,
                        "Absent: No clock-in recorded on a scheduled workday."
                    );
                    $this->line("        - Marked '{$absentUser->name}' as absent.");
                } catch (\Exception $e) {
                    Log::error("Failed to create absence record for user {$absentUser->id}: " . $e->getMessage());
                }
            }
        });
    }

    /**
     * Determines if a given date is a working day based on a time rule config.
     */
    private function isWorkDay(array $timeRules, Carbon $date): bool
    {
        // Check holidays first, as they override weekly schedules.
        foreach (($timeRules['holidays'] ?? []) as $holiday) {
            if ($date->isSameDay($holiday['date'] ?? null)) {
                return false; // It's a holiday
            }
        }

        // Check the weekly schedule for the given day of the week.
        $dayOfWeek = strtolower($date->format('l'));
        $daySchedule = $timeRules['weekly_schedule'][$dayOfWeek] ?? null;

        // If the day is defined and enabled, it's a workday.
        return $daySchedule && ($daySchedule['enabled'] ?? false);
    }

    /**
     * Returns the QUERY BUILDER for users that a specific constraint applies to.
     * It does not execute the query.
     */
    private function getUsersQueryForConstraint(AttendanceConstraint $constraint): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::query();

        // If the constraint is for a specific user, target only them.
        if ($constraint->user_id) {
            return $query->where('id', $constraint->user_id);
        }

        // If the constraint is for specific branches, find users in those branches.
        if (!empty($constraint->branch_ids)) {
            // This assumes a user's branch is stored in the 'user_professional_datas' table.
            return $query->whereHas('userProfessionalData', function ($q) use ($constraint) {
                $q->whereIn('branch_id', $constraint->branch_ids);
            });
        }

        // Otherwise, it's a global constraint for the entire company.
        return $query->where('company_id', $constraint->company_id);
    }
}
