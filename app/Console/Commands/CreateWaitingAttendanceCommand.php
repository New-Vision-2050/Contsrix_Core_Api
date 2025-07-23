<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Modules\Attendance\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class CreateWaitingAttendanceCommand extends Command
{
    protected $signature = 'attendance:create-waiting {--date= : Optional date in Y-m-d format to process (defaults to today)}';
    protected $description = 'Creates waiting attendance records for users who are expected to work today';

    private AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        parent::__construct();
        $this->attendanceService = $attendanceService;
    }

    public function handle()
    {
        $this->info('Starting creation of waiting attendance records...');

        // Allow running the command for a specific date for testing
        $dateToProcess = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $this->info("Creating waiting records for date: " . $dateToProcess->toDateString());

        // 1. Get all active constraints that contain time-based rules
        $constraints = AttendanceConstraint::query()
            ->where('is_active', true)
            ->whereJsonContainsKey('constraint_config->time_rules')
            ->get();
        if ($constraints->isEmpty()) {
            $this->warn('No active time-based constraints found. Exiting.');
            return Command::SUCCESS;
        }

        // Process each constraint to find the employees it applies to
        foreach ($constraints as $constraint) {
            /** @var AttendanceConstraint $constraint */
            $this->processConstraint($constraint, $dateToProcess);
        }

        $this->info('Waiting attendance records created successfully.');
        return Command::SUCCESS;
    }

    /**
     * Process a single constraint to create waiting records for applicable users
     */
    private function processConstraint(AttendanceConstraint $constraint, Carbon $date): void
    {
        $this->line("--> Processing Constraint: '{$constraint->constraint_name}'");

        // Check if the date is a scheduled workday according to this constraint
        if (!$this->isWorkDay($constraint->constraint_config['time_rules'] ?? [], date: $date)) {
            $this->line("    -> Not a scheduled work day for this constraint. Skipping.");
            return;
        }

        $this->info("    -> Is a scheduled work day. Processing employees...");

        // Get the query builder for users this constraint applies to
        $usersQuery = $this->getUsersQueryForConstraint($constraint);
        // Log the SQL query for debugging. Remove in production.
        // Added line
        // $this->info("waitingUserIds --->".$usersQuery);
        // Process users in manageable chunks to keep memory usage low
        $usersQuery->chunkById(200, function ($users) use ($date, $constraint) {
            if ($users->isEmpty()) {
                return;
            }

            // Get schedule from constraint once
            $timeRules = $constraint->constraint_config['time_rules'] ?? [];
            $dayOfWeek = strtolower($date->format('l'));
            $daySchedule = $timeRules['weekly_schedule'][$dayOfWeek] ?? null;

            // If there's no schedule or no periods for the day, skip.
            if (empty($daySchedule) || empty($daySchedule['periods'])) {
                $this->info("    -> No defined work periods for this day. Skipping chunk.");
                return;
            }
            // Iterate through each user in the chunk
            foreach ($users as $user) {
                // For each user, iterate through the day's scheduled periods
                foreach ($daySchedule['periods'] as $index => $period) {
                    // Check if an attendance record already exists for this specific user and period
                    $existingRecord = $this->attendanceService->getPresentUserIdsOnDate([$user->id], $date, $period);
                     $this->info('Existing record: '. count($existingRecord) .'');
                    // If no record exists, create a new 'waiting' record
                    
                    if (empty($existingRecord)) {
                        try {
                            $startTime = $period['start_time'] ?? 'Unknown';
                            $endTime = $period['end_time'] ?? 'Unknown';
                            $periodNumber = $index + 1;
                            $notes = "Waiting for attendance for Period {$periodNumber} ({$startTime} - {$endTime}).";

                            $this->attendanceService->createWaitingRecord($user, $date, $notes,$startTime,$endTime);
                            $this->line("        - Created waiting record for '{$user->name}' (Period {$periodNumber})");
                        } catch (\Exception $e) {
                            Log::error("Failed to create waiting record for user {$user->id} for period {$periodNumber}: " . $e->getMessage());
                            $this->error("        - Error for '{$user->name}' (Period {$periodNumber}): {$e->getMessage()}");
                        }
                    }
                }
            }
        });
    }

    /**
     * Determines if a given date is a working day based on a time rule config
     */
    private function isWorkDay(array $timeRules, Carbon $date): bool
    {
        // Check holidays first, as they override weekly schedules
        foreach (($timeRules['holidays'] ?? []) as $holiday) {
            if ($date->isSameDay($holiday['date'] ?? null)) {
                return false; // It's a holiday
            }
        }

        // Check the weekly schedule for the given day of the week
        $dayOfWeek = strtolower($date->format('l'));
        $daySchedule = $timeRules['weekly_schedule'][$dayOfWeek] ?? null;

        // If the day is defined and enabled, it's a workday
        return $daySchedule && ($daySchedule['enabled'] ?? false);
    }

    /**
     * Returns the QUERY BUILDER for users that a specific constraint applies to
     */
    private function getUsersQueryForConstraint(AttendanceConstraint $constraint)//: Builder
    {
       $query = User::where('company_id', $constraint->company_id);
       $branchIds = $constraint->branch_ids ?? [];
       $this->info(json_encode($branchIds));

        if (!empty($branchIds)) {
            $query->whereHas('userProfessionalData', function ($q) use ($branchIds) {
                $q->whereIn('branch_id',$branchIds);
            });
        }

        return $query->where('company_id', $constraint->company_id);
    }

        /**
     * Returns the QUERY BUILDER for users that a specific constraint applies to.
     */
    private function getUsersQueryForConstraint2(AttendanceConstraint $constraint): Builder
    {
        $query = User::where('company_id', $constraint->company_id);

        // Filter by specific user_ids if the constraint has them
        if (!empty($constraint->user_ids)) {
            $query->whereIn('id', $constraint->user_ids);
        } else {
            // Otherwise, if branch_ids are specified, filter by users in those branches
            $branchIds = $constraint->branch_ids ?? [];
            if (!empty($branchIds)) {
                $query->whereHas('userProfessionalData', function ($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                });
            }
            // If neither user_ids nor branch_ids are specified, the constraint applies to all users in the company
            // In this case, no additional WHERE clause for user/branch is needed beyond company_id.
        }

        // Eager load relations needed by AttendanceConstraintService for getTodaysWorkRulesForUser (used in processCompanyAttendance)
        // Ensure userProfessionalData and its nested relations are loaded for constraint service.
        $query->with([
            'userProfessionalData.branch.defaultAttendanceConstraint',
            'userProfessionalData.attendanceConstraint',
            'userProfessionalData.company', // For timezone, etc.
        ]);

        return $query;
    }
}