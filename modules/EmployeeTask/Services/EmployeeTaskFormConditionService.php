<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\Carbon;
use Modules\Attendance\Repositories\AttendanceRepository;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * Evaluates InternalProcessForm conditions stored on child ProcedureSetting records.
 *
 * Supports two storage formats:
 *
 *   OLD (flat object, used by createTask / endTask):
 *     {"allow_during_shift": true, "can_exit_outside_location": false}
 *
 *   NEW (rich array, used by startTask and beyond):
 *     [
 *       {"key": "inside_shift_time",   "is_active": true,  "sort_order": 1, "settings": {"start_time": "08:00", ...}},
 *       {"key": "inside_task_location","is_active": true,  "sort_order": 2, "settings": {"radius_meters": 100}},
 *       {"key": "employee_has_attendance","is_active": true,"sort_order": 3, "settings": {}},
 *       {"key": "task_is_approved",    "is_active": false, "sort_order": 4, "settings": {}},
 *       {"key": "no_open_task",        "is_active": true,  "sort_order": 5, "settings": {}}
 *     ]
 *
 * Format is auto-detected: a list (array_is_list) = new; associative = old.
 */
final class EmployeeTaskFormConditionService
{
    public function __construct(
        private readonly AttendanceConstraintService $attendanceConstraintService,
        private readonly AttendanceRepository        $attendanceRepository,
        private readonly EmployeeTaskLocationService  $locationService,
        private readonly ProcedureWorkflowService     $workflow,
    ) {}

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Check shift/holiday conditions for the createTask form.
     *
     * @throws EmployeeTaskException
     */
    public function checkCreateTaskConditions(
        string  $userId,
        string  $companyId,
        ?string $branchId,
    ): void {
        $conditions = $this->resolveConditions(
            InternalProcessForm::CreateTask->value,
            $companyId,
            $branchId,
        );

        if ($conditions === null) {
            return;
        }

        $map = $this->indexConditions($conditions);
        $this->assertShiftConditions($map, $userId);
    }

    /**
     * Check all active conditions for the startTask form (new rich format).
     *
     * Conditions evaluated (each only when is_active = true):
     *   inside_shift_time       — current time is within the configured time window
     *   inside_task_location    — employee is within settings.radius_meters of task location
     *   employee_has_attendance — employee has an active clocked-in attendance record
     *   task_is_approved        — task status is approved (or beyond)
     *   no_open_task            — employee has no other task currently in_progress
     *
     * @throws EmployeeTaskException
     */
    public function checkStartTaskConditions(
        EmployeeTaskRequest $task,
        User                $user,
        float               $latitude,
        float               $longitude,
    ): void {
        $task->loadMissing('user.userProfessionalData');
        $branchId = $task->user?->userProfessionalData?->branch_id !== null
            ? (string) $task->user->userProfessionalData->branch_id
            : null;

        $conditions = $this->resolveConditions(
            InternalProcessForm::StartTask->value,
            $task->company_id,
            $branchId,
        );

        if ($conditions === null) {
            return;
        }

        $map = $this->indexConditions($conditions);

        // ── inside_shift_time ────────────────────────────────────────────────
        $shiftCond = $map[InternalProcessCondition::InsideShiftTime->value] ?? null;
        if ($shiftCond && ($shiftCond['is_active'] ?? false)) {
            $this->assertInsideShiftTime($shiftCond['settings'] ?? []);
        }

        // ── inside_task_location ─────────────────────────────────────────────
        $locationCond = $map[InternalProcessCondition::InsideTaskLocation->value] ?? null;
        if ($locationCond && ($locationCond['is_active'] ?? false)) {
            $radius = (int) ($locationCond['settings']['radius_meters'] ?? 100);
            $this->assertInsideTaskLocation($task, $user, $latitude, $longitude, $radius);
        }

        // ── employee_has_attendance ──────────────────────────────────────────
        $attendanceCond = $map[InternalProcessCondition::EmployeeHasAttendance->value] ?? null;
        if ($attendanceCond && ($attendanceCond['is_active'] ?? false)) {
            $this->assertEmployeeHasAttendance((string) $user->id);
        }

        // ── task_is_approved ─────────────────────────────────────────────────
        $approvedCond = $map[InternalProcessCondition::TaskIsApproved->value] ?? null;
        if ($approvedCond && ($approvedCond['is_active'] ?? false)) {
            $this->assertTaskIsApproved($task);
        }

        // ── no_open_task ─────────────────────────────────────────────────────
        $noOpenCond = $map[InternalProcessCondition::NoOpenTask->value] ?? null;
        if ($noOpenCond && ($noOpenCond['is_active'] ?? false)) {
            $this->assertNoOpenTask((string) $user->id, (string) $task->id);
        }

        // ── Legacy: must_be_in_location (old flat format, backward-compat) ──
        if (isset($map[InternalProcessCondition::MustBeInLocation->value])) {
            $mustBe = (bool) ($map[InternalProcessCondition::MustBeInLocation->value]['is_active'] ?? false);
            if ($mustBe) {
                $inLocation = $this->locationService->isWithinTaskRadiusForStart($task, $user, $latitude, $longitude);
                if (! $inLocation) {
                    throw EmployeeTaskException::cannotStartTaskOutsideLocation();
                }
            }
        }
    }

    /**
     * Check location condition for the endTask form.
     *
     * @throws EmployeeTaskException
     */
    public function checkEndTaskConditions(
        EmployeeTaskRequest $task,
        float               $latitude,
        float               $longitude,
    ): void {
        $task->loadMissing('user.userProfessionalData');
        $branchId = $task->user?->userProfessionalData?->branch_id !== null
            ? (string) $task->user->userProfessionalData->branch_id
            : null;

        $conditions = $this->resolveConditions(
            InternalProcessForm::EndTask->value,
            $task->company_id,
            $branchId,
        );

        if ($conditions === null) {
            return;
        }

        $map = $this->indexConditions($conditions);

        $canExitOutside = (bool) ($map[InternalProcessCondition::CanExitOutsideLocation->value]['is_active']
            ?? $map[InternalProcessCondition::CanExitOutsideLocation->value]
            ?? true);

        if (! $canExitOutside) {
            $inLocation = $this->locationService->isWithinTaskRadius($task, $latitude, $longitude);
            if (! $inLocation) {
                throw EmployeeTaskException::cannotEndTaskOutsideLocation();
            }
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Resolve stored conditions from the matching child ProcedureSetting.
     * Returns null when no setting or conditions are empty → check passes silently.
     */
    private function resolveConditions(
        string  $formKey,
        string  $companyId,
        ?string $branchId,
    ): ?array {
        $setting = $this->workflow->resolveInternalProcedureSettingByForm(
            ProcedureSettingType::EmployeeTask->value,
            $formKey,
            $companyId,
            $branchId,
        );

        if ($setting === null || empty($setting->conditions)) {
            return null;
        }

        return $setting->conditions;
    }

    /**
     * Normalize conditions into a keyed map: ['condition_key' => conditionObject].
     *
     * New format (list of objects) → keyed by 'key' field.
     * Old format (associative)     → wrapped: ['key' => $key, 'is_active' => $val, 'settings' => []].
     *
     * @return array<string, array{key: string, is_active: bool, sort_order: int, settings: array}>
     */
    private function indexConditions(array $conditions): array
    {
        if (array_is_list($conditions)) {
            $map = [];
            foreach ($conditions as $item) {
                if (isset($item['key'])) {
                    $map[$item['key']] = $item;
                }
            }

            return $map;
        }

        // Old flat associative format
        $map = [];
        foreach ($conditions as $key => $value) {
            $map[$key] = [
                'key'        => $key,
                'is_active'  => is_bool($value) ? $value : (bool) $value,
                'sort_order' => 0,
                'settings'   => [],
            ];
        }

        return $map;
    }

    /**
     * Evaluate AllowDuringShift / AllowOutsideShift / AllowOnHolidays.
     * Works with both old flat format and new normalized map.
     */
    private function assertShiftConditions(array $map, string $userId): void
    {
        $user = User::query()
            ->with([
                'professionalData.attendanceConstraint',
                'userProfessionalData.branch',
                'userProfessionalData.department',
            ])
            ->find($userId);

        if ($user === null) {
            return;
        }

        $workRules     = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user);
        $isHoliday     = (bool) ($workRules['is_holiday'] ?? false);
        $isDuringShift = ($workRules['current_work_period'] ?? null) !== null;

        if ($isHoliday) {
            $allowOnHolidays = (bool) ($map[InternalProcessCondition::AllowOnHolidays->value]['is_active'] ?? true);
            if (! $allowOnHolidays) {
                throw EmployeeTaskException::notAllowedOnHolidays();
            }

            return;
        }

        if ($isDuringShift) {
            $allowDuringShift = (bool) ($map[InternalProcessCondition::AllowDuringShift->value]['is_active'] ?? true);
            if (! $allowDuringShift) {
                throw EmployeeTaskException::notAllowedDuringShift();
            }
        } else {
            $allowOutsideShift = (bool) ($map[InternalProcessCondition::AllowOutsideShift->value]['is_active'] ?? true);
            if (! $allowOutsideShift) {
                throw EmployeeTaskException::notAllowedOutsideShift();
            }
        }
    }

    /**
     * Check that the current time falls within the configured shift time window.
     *
     * Effective window:
     *   start = start_time  − allow_before_start_minutes
     *   end   = end_time    − allow_before_end_minutes   (must finish X min before shift end)
     *
     * @throws EmployeeTaskException
     */
    private function assertInsideShiftTime(array $settings): void
    {
        $startTime           = $settings['start_time'] ?? '00:00';
        $endTime             = $settings['end_time']   ?? '23:59';
        $beforeStartMinutes  = (int) ($settings['allow_before_start_minutes'] ?? 0);
        $beforeEndMinutes    = (int) ($settings['allow_before_end_minutes']   ?? 0);

        $now            = Carbon::now();
        $effectiveStart = Carbon::parse($startTime)->subMinutes($beforeStartMinutes);
        $effectiveEnd   = Carbon::parse($endTime)->subMinutes($beforeEndMinutes);

        if ($now->lt($effectiveStart) || $now->gt($effectiveEnd)) {
            throw EmployeeTaskException::outsideShiftTimeWindow();
        }
    }

    /**
     * Check the employee is within the given radius of the task location.
     *
     * @throws EmployeeTaskException
     */
    private function assertInsideTaskLocation(
        EmployeeTaskRequest $task,
        User                $user,
        float               $latitude,
        float               $longitude,
        int                 $radiusMeters,
    ): void {
        if ((float) $task->task_latitude === 0.0 && (float) $task->task_longitude === 0.0) {
            return;
        }

        $distance = \Modules\EmployeeTask\Support\GeoDistance::metres(
            (float) $task->task_latitude,
            (float) $task->task_longitude,
            $latitude,
            $longitude,
        );

        if ($distance > $radiusMeters) {
            throw EmployeeTaskException::cannotStartTaskOutsideLocation();
        }
    }

    /**
     * Check the user has an active (clocked-in) attendance record today.
     *
     * @throws EmployeeTaskException
     */
    private function assertEmployeeHasAttendance(string $userId): void
    {
        $attendance = $this->attendanceRepository->getCurrentAttendance(
            Uuid::fromString($userId),
            withUser: false,
        );

        if ($attendance === null) {
            throw EmployeeTaskException::employeeHasNoAttendance();
        }
    }

    /**
     * Check the task is in approved status (ready to start).
     *
     * @throws EmployeeTaskException
     */
    private function assertTaskIsApproved(EmployeeTaskRequest $task): void
    {
        if (! $task->isInStatus(EmployeeTaskStatus::Approved)) {
            throw EmployeeTaskException::taskNotApproved();
        }
    }

    /**
     * Check the user does not have another task currently in_progress.
     *
     * @throws EmployeeTaskException
     */
    private function assertNoOpenTask(string $userId, string $excludeTaskId): void
    {
        $hasOpen = EmployeeTaskRequest::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $excludeTaskId)
            ->where('status', EmployeeTaskStatus::InProgress->value)
            ->exists();

        if ($hasOpen) {
            throw EmployeeTaskException::hasOtherOpenTask();
        }
    }
}
