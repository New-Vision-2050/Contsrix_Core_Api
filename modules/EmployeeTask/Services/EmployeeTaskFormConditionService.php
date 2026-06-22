<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\Carbon;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;

/**
 * Evaluates InternalProcessForm conditions stored on child ProcedureSetting records.
 *
 * Active forms:
 *   createTask — rich-array format with mode-aware AllowDuringShift (shift | specific_time)
 *
 * startTask and endTask have no conditions; their check methods are no-ops.
 *
 * Storage format (createTask):
 *   NEW (rich array):
 *     [{"key": "allow_during_shift", "is_active": true, "sort_order": 1,
 *       "settings": {"mode": "specific_time", "start_time": "08:00", "end_time": "17:00"}}]
 *
 *   OLD (flat associative, backward-compat for existing DB rows):
 *     {"allow_during_shift": true, ...}
 */
final class EmployeeTaskFormConditionService
{
    public function __construct(
        private readonly AttendanceConstraintService $attendanceConstraintService,
        private readonly ProcedureWorkflowService     $workflow,
    ) {}

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Check all createTask conditions:
     *   - shift / holiday gating  (AllowDuringShift / AllowOutsideShift / AllowOnHolidays)
     *   - maximum task duration   (MaxTaskDuration)
     *   - maximum scheduled date  (MaxScheduledDateOffset)
     *
     * @throws EmployeeTaskException
     */
    public function checkCreateTaskConditions(
        string  $userId,
        string  $companyId,
        ?string $branchId,
        float   $durationHours,
        string  $taskDate,
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

        // ── max_task_duration ────────────────────────────────────────────────
        $maxDurationCond = $map[InternalProcessCondition::MaxTaskDuration->value] ?? null;
        if ($maxDurationCond && ($maxDurationCond['is_active'] ?? false)) {
            $this->assertMaxTaskDuration($durationHours, $maxDurationCond['settings'] ?? []);
        }

        // ── max_scheduled_date_offset ─────────────────────────────────────────
        $maxDateCond = $map[InternalProcessCondition::MaxScheduledDateOffset->value] ?? null;
        if ($maxDateCond && ($maxDateCond['is_active'] ?? false)) {
            $this->assertMaxScheduledDateOffset($taskDate, $maxDateCond['settings'] ?? []);
        }
    }

    /**
     * No conditions are configured for startTask.
     */
    public function checkStartTaskConditions(
        EmployeeTaskRequest $task,
        User                $user,
        float               $latitude,
        float               $longitude,
    ): void {}

    /**
     * No conditions are configured for endTask.
     */
    public function checkEndTaskConditions(
        EmployeeTaskRequest $task,
        float               $latitude,
        float               $longitude,
    ): void {}

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
     *
     * AllowDuringShift now supports two modes via settings.mode:
     *   'shift'         (default) — checks the employee's actual attendance schedule.
     *   'specific_time' — checks the current time against a fixed start_time/end_time window.
     */
    private function assertShiftConditions(array $map, string $userId): void
    {
        $duringShiftCond = $map[InternalProcessCondition::AllowDuringShift->value] ?? null;

        // specific_time mode: bypass attendance system, check fixed time window instead
        if ($duringShiftCond && ($duringShiftCond['is_active'] ?? false)) {
            $mode = $duringShiftCond['settings']['mode'] ?? 'shift';
            if ($mode === 'specific_time') {
                $this->assertInsideSpecificTimeWindow($duringShiftCond['settings'] ?? []);

                return;
            }
        }

        // shift mode (default): use attendance constraint system
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
            $allowDuringShift = (bool) ($duringShiftCond['is_active'] ?? true);
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
     * Enforce the maximum allowed task duration.
     *
     * @throws EmployeeTaskException
     */
    private function assertMaxTaskDuration(float $durationHours, array $settings): void
    {
        $maxHours = (int) ($settings['max_hours'] ?? 8);
        if ($durationHours > $maxHours) {
            throw EmployeeTaskException::taskDurationExceedsLimit($maxHours);
        }
    }

    /**
     * Enforce the maximum number of days from today for the task's scheduled date.
     *
     * @throws EmployeeTaskException
     */
    private function assertMaxScheduledDateOffset(string $taskDate, array $settings): void
    {
        $maxDays = (int) ($settings['max_days'] ?? 30);
        $limit   = Carbon::today()->addDays($maxDays);
        $date    = Carbon::parse($taskDate)->startOfDay();

        if ($date->gt($limit)) {
            throw EmployeeTaskException::taskDateTooFarInFuture($maxDays);
        }
    }

    /**
     * Check the current time falls within a fixed start_time / end_time window.
     * Used by AllowDuringShift when settings.mode = 'specific_time'.
     *
     * @throws EmployeeTaskException
     */
    private function assertInsideSpecificTimeWindow(array $settings): void
    {
        $startTime = $settings['start_time'] ?? '00:00';
        $endTime   = $settings['end_time']   ?? '23:59';

        $now   = Carbon::now();
        $start = Carbon::parse($startTime);
        $end   = Carbon::parse($endTime);

        if ($now->lt($start) || $now->gt($end)) {
            throw EmployeeTaskException::outsideShiftTimeWindow();
        }
    }

}
