<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\Carbon;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Support\GeoDistance;
use Modules\EmployeeTask\Support\GeoPolygon;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\ProcedureSetting\Services\WorkflowEngine;
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
        private readonly WorkflowEngine               $engine,
    ) {}

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Check all createTask conditions:
     *   - shift / holiday gating  (AllowDuringShift / AllowOnHolidays)
     *   - location gating         (AllowOutsideShift — now location-based)
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
        float   $taskLatitude,
        float   $taskLongitude,
        ?float  $currentLatitude = null,
        ?float  $currentLongitude = null,
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
        $this->assertLocationConditions($map, $userId, $currentLatitude, $currentLongitude);
        $this->assertCustomLocationConditions($map, $taskLatitude, $taskLongitude);

        // ── max_task_duration ────────────────────────────────────────────────
        $maxDurationCond = $map[InternalProcessCondition::MaxTaskDuration->value] ?? null;
        if ($maxDurationCond && ($maxDurationCond['is_active'] ?? false)) {
            $this->assertMaxTaskDuration($durationHours, $maxDurationCond['settings'] ?? []);
        }

        // ── max_scheduled_date_offset ─────────────────────────────────────────
        $maxDateCond = $map[InternalProcessCondition::MaxScheduledDateOffset->value] ?? null;
        if ($maxDateCond && ($maxDateCond['is_active'] ?? false)) {
            $this->assertMaxScheduledDateOffset($userId, $taskDate, $maxDateCond['settings'] ?? []);
        }
    }

    /**
     * Evaluate precondition-type (form_group = 'precondition') createTask conditions
     * and return individual pass/fail results without throwing.
     *
     * ALWAYS returns all 3 preconditions so the mobile app can show a fixed
     * checklist UI. When a condition is not configured, it shows as passed
     * (green checkmark) because the admin is not enforcing it.
     *
     * @return array{
     *   all_passed: bool,
     *   conditions: list<array{key: string, label_ar: string, passed: bool, message: ?string}>
     * }
     */
    public function getPreConditionResults(
        string  $userId,
        string  $companyId,
        ?string $branchId,
        ?float  $currentLatitude = null,
        ?float  $currentLongitude = null,
    ): array {
        $conditions = $this->resolveConditions(
            InternalProcessForm::CreateTask->value,
            $companyId,
            $branchId,
        );

        $map = $conditions === null ? [] : $this->indexConditions($conditions);
        $results = [];
        $allPassed = true;

        // ── 1. Shift check ───────────────────────────────────────────────────
        $shiftResult = $this->evaluateShiftCondition($map, $userId);
        if ($shiftResult === null) {
            $shiftResult = [
                'key'      => InternalProcessCondition::AllowDuringShift->value,
                'label_ar' => InternalProcessCondition::AllowDuringShift->labelAr(),
                'passed'   => true,
                'message'  => null,
            ];
        }
        $results[] = [
            'key'      => $shiftResult['key'],
            'label_ar' => $shiftResult['label_ar'],
            'passed'   => $shiftResult['passed'],
            'message'  => $shiftResult['message'],
        ];
        if (! $shiftResult['passed']) {
            $allPassed = false;
        }

        // ── 2. Holiday check ─────────────────────────────────────────────────
        $holidayResult = $this->evaluateHolidayCondition($map, $userId);
        if ($holidayResult === null) {
            $holidayResult = [
                'key'      => InternalProcessCondition::AllowOnHolidays->value,
                'label_ar' => InternalProcessCondition::AllowOnHolidays->labelAr(),
                'passed'   => true,
                'message'  => null,
            ];
        }
        $results[] = [
            'key'      => $holidayResult['key'],
            'label_ar' => $holidayResult['label_ar'],
            'passed'   => $holidayResult['passed'],
            'message'  => $holidayResult['message'],
        ];
        if (! $holidayResult['passed']) {
            $allPassed = false;
        }

        // ── 3. Location check (employee current location vs work areas) ──────
        $locationResult = $this->evaluateLocationCondition($map, $userId, $currentLatitude, $currentLongitude);
        if ($locationResult === null) {
            $locationResult = [
                'key'      => 'location_inside_work_area',
                'label_ar' => 'التواجد داخل نطاق العمل',
                'passed'   => true,
                'message'  => null,
            ];
        }
        $results[] = [
            'key'      => $locationResult['key'],
            'label_ar' => $locationResult['label_ar'],
            'passed'   => $locationResult['passed'],
            'message'  => $locationResult['message'],
        ];
        if (! $locationResult['passed']) {
            $allPassed = false;
        }

        return [
            'all_passed' => $allPassed,
            'conditions' => $results,
        ];
    }

    /**
     * Return active in_form conditions for the createTask form so the mobile
     * app can display them as hints/constraints before the employee submits.
     *
     * Output is NORMALIZED — every item has the same shape:
     *   key, label_ar, is_active, mode, constraints
     *
     * Includes: max_task_duration, max_scheduled_date_offset,
     *           inside_custom_locations, has_task_duration, etc.
     *
     * @return list<array{key: string, label_ar: string, is_active: true, mode: ?string, constraints: array}>
     */
    public function getInFormConditionsPreview(
        string  $companyId,
        ?string $branchId,
    ): array {
        $conditions = $this->resolveConditions(
            InternalProcessForm::CreateTask->value,
            $companyId,
            $branchId,
        );

        if ($conditions === null) {
            return [];
        }

        $map = $this->indexConditions($conditions);
        $results = [];

        foreach ($map as $item) {
            $condEnum = InternalProcessCondition::tryFrom($item['key'] ?? '');
            if ($condEnum === null) {
                continue;
            }

            if ($condEnum->formGroup() !== 'in_form') {
                continue;
            }

            if (! ($item['is_active'] ?? false)) {
                continue;
            }

            $settings = $item['settings'] ?? [];

            $preview = match ($condEnum) {
                InternalProcessCondition::MaxTaskDuration => [
                    'mode'        => null,
                    'constraints' => ['max_hours' => (int) ($settings['max_hours'] ?? 8)],
                ],

                InternalProcessCondition::MaxScheduledDateOffset => [
                    'mode'        => $settings['mode'] ?? 'max_task_date',
                    'constraints' => ($settings['mode'] ?? 'max_task_date') === 'max_task_date'
                        ? ['max_days' => (int) ($settings['max_days'] ?? 30)]
                        : [],
                ],

                InternalProcessCondition::InsideCustomLocations => [
                    'mode'        => null,
                    'constraints' => ['polygons' => $settings['polygons'] ?? []],
                ],

                InternalProcessCondition::HasTaskDuration => [
                    'mode'        => null,
                    'constraints' => ['required' => true],
                ],

                InternalProcessCondition::MaxDurationHours => [
                    'mode'        => null,
                    'constraints' => ['max_hours' => (int) ($settings['max_hours'] ?? 8)],
                ],

                InternalProcessCondition::MaxAttachments => [
                    'mode'        => null,
                    'constraints' => ['max_count' => (int) ($settings['max_count'] ?? 5)],
                ],

                default => ['mode' => null, 'constraints' => []],
            };

            $results[] = [
                'key'         => $condEnum->value,
                'label_ar'    => $condEnum->labelAr(),
                'is_active'   => true,
                'mode'        => $preview['mode'],
                'constraints' => $preview['constraints'],
            ];
        }

        return $results;
    }

    /**
     * Check startTask conditions:
     *   - holiday gating (AllowOnHolidays)
     *
     * @throws EmployeeTaskException
     */
    public function checkStartTaskConditions(
        EmployeeTaskRequest $task,
        User                $user,
        float               $latitude,
        float               $longitude,
    ): void {
        $companyId = (string) tenant('id');
        $branchId = $user->userProfessionalData?->branch_id !== null
            ? (string) $user->userProfessionalData->branch_id
            : null;

        $conditions = $this->resolveConditions(
            InternalProcessForm::StartTask->value,
            $companyId,
            $branchId,
        );

        if ($conditions === null) {
            return;
        }

        $map = $this->indexConditions($conditions);

        $holidayResult = $this->evaluateHolidayCondition($map, (string) $user->id);
        if ($holidayResult !== null && ! $holidayResult['passed']) {
            throw EmployeeTaskException::notAllowedOnHolidays();
        }
    }

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
     *
     * NOTE: Uses WorkflowEngine::resolveSettingsForEntry directly instead of
     * ProcedureWorkflowService::resolveInternalProcedureSettingByForm because
     * the latter returns null when steps are empty, even if conditions exist.
     */
    private function resolveConditions(
        string  $formKey,
        string  $companyId,
        ?string $branchId,
    ): ?array {
        $settings = $this->engine->resolveSettingsForEntry(
            ProcedureSettingType::EmployeeTask->value,
            $formKey,
            $companyId,
            $branchId,
        );

        $setting = $settings->first();

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
     * Evaluate AllowDuringShift / AllowOnHolidays.
     * Works with both old flat format and new normalized map.
     *
     * AllowDuringShift now supports two modes via settings.mode:
     *   'shift'         (default) — checks the employee's actual attendance schedule.
     *   'specific_time' — checks the current time against a fixed start_time/end_time window.
     *
     * NOTE: AllowOutsideShift has been repurposed as a location condition
     *       and is evaluated separately in assertLocationConditions().
     */
    private function assertShiftConditions(array $map, string $userId): void
    {
        $shiftResult = $this->evaluateShiftCondition($map, $userId);
        if ($shiftResult !== null && ! $shiftResult['passed']) {
            match ($shiftResult['exception']) {
                'notAllowedDuringShift'  => throw EmployeeTaskException::notAllowedDuringShift(),
                'outsideShiftTimeWindow' => throw EmployeeTaskException::outsideShiftTimeWindow(),
            };
        }

        $holidayResult = $this->evaluateHolidayCondition($map, $userId);
        if ($holidayResult !== null && ! $holidayResult['passed']) {
            throw EmployeeTaskException::notAllowedOnHolidays();
        }
    }

    /**
     * Evaluate holiday precondition.
     * Returns null when the condition is not configured / not enforced.
     *
     * @return array{key: string, label_ar: string, passed: bool, message: ?string, exception: string}|null
     */
    private function evaluateHolidayCondition(array $map, string $userId): ?array
    {
        $user = User::query()
            ->with([
                'professionalData.attendanceConstraint',
                'userProfessionalData.branch',
                'userProfessionalData.department',
            ])
            ->find($userId);

        if ($user === null) {
            return null;
        }

        $workRules = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user);
        $isHoliday = (bool) ($workRules['is_holiday'] ?? false);

        if (! $isHoliday) {
            return null; // not a holiday → no holiday check needed
        }

        $allowOnHolidays = (bool) ($map[InternalProcessCondition::AllowOnHolidays->value]['is_active'] ?? true);

        if ($allowOnHolidays) {
            return [
                'key'       => InternalProcessCondition::AllowOnHolidays->value,
                'label_ar'  => InternalProcessCondition::AllowOnHolidays->labelAr(),
                'passed'    => true,
                'message'   => null,
                'exception' => 'notAllowedOnHolidays',
            ];
        }

        return [
            'key'       => InternalProcessCondition::AllowOnHolidays->value,
            'label_ar'  => InternalProcessCondition::AllowOnHolidays->labelAr(),
            'passed'    => false,
            'message'   => 'Task creation is not allowed on holidays.',
            'exception' => 'notAllowedOnHolidays',
        ];
    }

    /**
     * Evaluate shift precondition.
     * Returns null when the condition is not configured / not enforced.
     *
     * @return array{key: string, label_ar: string, passed: bool, message: ?string, exception: string}|null
     */
    private function evaluateShiftCondition(array $map, string $userId): ?array
    {
        $duringShiftCond = $map[InternalProcessCondition::AllowDuringShift->value] ?? null;

        // Not configured → skip
        if ($duringShiftCond === null) {
            return null;
        }

        $isActive = (bool) ($duringShiftCond['is_active'] ?? false);

        // Inactive → not enforcing shift requirement
        if (! $isActive) {
            return null;
        }

        $mode = $duringShiftCond['settings']['mode'] ?? 'shift';

        // specific_time mode
        if ($mode === 'specific_time') {
            $startTime = $duringShiftCond['settings']['start_time'] ?? '00:00';
            $endTime   = $duringShiftCond['settings']['end_time']   ?? '23:59';
            $now       = Carbon::now();
            $start     = Carbon::parse($startTime);
            $end       = Carbon::parse($endTime);
            $inWindow  = ! ($now->lt($start) || $now->gt($end));

            if ($inWindow) {
                return [
                    'key'       => InternalProcessCondition::AllowDuringShift->value,
                    'label_ar'  => InternalProcessCondition::AllowDuringShift->labelAr(),
                    'passed'    => true,
                    'message'   => null,
                    'exception' => 'outsideShiftTimeWindow',
                ];
            }

            return [
                'key'       => InternalProcessCondition::AllowDuringShift->value,
                'label_ar'  => InternalProcessCondition::AllowDuringShift->labelAr(),
                'passed'    => false,
                'message'   => 'Current time is outside the allowed shift window.',
                'exception' => 'outsideShiftTimeWindow',
            ];
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
            return null;
        }

        $workRules     = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user);
        $isHoliday     = (bool) ($workRules['is_holiday'] ?? false);

        if ($isHoliday) {
            return null; // holiday logic handled by evaluateHolidayCondition
        }

        $isDuringShift = ($workRules['current_work_period'] ?? null) !== null;

        if ($isDuringShift) {
            return [
                'key'       => InternalProcessCondition::AllowDuringShift->value,
                'label_ar'  => InternalProcessCondition::AllowDuringShift->labelAr(),
                'passed'    => true,
                'message'   => null,
                'exception' => 'notAllowedDuringShift',
            ];
        }

        return [
            'key'       => InternalProcessCondition::AllowDuringShift->value,
            'label_ar'  => InternalProcessCondition::AllowDuringShift->labelAr(),
            'passed'    => false,
            'message'   => 'You are not currently within your work shift.',
            'exception' => 'notAllowedDuringShift',
        ];
    }

    /**
     * Evaluate AllowOutsideShift as a location-based condition.
     * If the condition is active (is_active = true), the employee is ALLOWED
     * to create tasks when outside their work location.
     * If inactive (is_active = false) and the employee is outside all work
     * locations, an exception is thrown.
     *
     * @throws EmployeeTaskException
     */
    private function assertLocationConditions(
        array $map,
        string $userId,
        ?float $currentLatitude,
        ?float $currentLongitude,
    ): void {
        $result = $this->evaluateLocationCondition($map, $userId, $currentLatitude, $currentLongitude);
        if ($result !== null && ! $result['passed']) {
            throw EmployeeTaskException::notAllowedOutsideLocation();
        }
    }

    /**
     * Evaluate location precondition.
     * Returns null when the condition is not configured / not enforced.
     *
     * @return array{key: string, label_ar: string, passed: bool, message: ?string}|null
     */
    private function evaluateLocationCondition(
        array $map,
        string $userId,
        ?float $currentLatitude,
        ?float $currentLongitude,
    ): ?array {
        $locationCond = $map[InternalProcessCondition::AllowOutsideShift->value] ?? null;
        $allowOutsideLocation = (bool) ($locationCond['is_active'] ?? true);

        // If allowed outside location, or condition not configured → skip check
        if ($allowOutsideLocation) {
            return null;
        }

        // No GPS data provided → cannot enforce location restriction
        if ($currentLatitude === null || $currentLongitude === null) {
            return [
                'key'      => 'location_inside_work_area',
                'label_ar'  => 'التواجد داخل نطاق العمل',
                'passed'    => false,
                'message'   => 'Location data is required to verify work area.',
            ];
        }

        $user = User::query()
            ->with([
                'professionalData.attendanceConstraint',
                'userProfessionalData.branch',
            ])
            ->find($userId);

        if ($user === null) {
            return null;
        }

        $workRules = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user);
        $mainLocation = $workRules['location_work'] ?? null;
        $additionalLocations = $workRules['additional_locations'] ?? [];

        // Gather all locations to check against
        $locations = [];
        if ($mainLocation && isset($mainLocation['latitude'], $mainLocation['longitude'], $mainLocation['radius'])) {
            $locations[] = $mainLocation;
        }
        foreach ($additionalLocations as $loc) {
            if (isset($loc['latitude'], $loc['longitude'], $loc['radius'])) {
                $locations[] = $loc;
            }
        }

        // If no work locations are configured, we cannot enforce the restriction
        if (empty($locations)) {
            return null;
        }

        $isInsideAnyLocation = false;
        foreach ($locations as $loc) {
            $radius = (int) ($loc['radius'] ?? 100);
            $distance = GeoDistance::metres(
                (float) $loc['latitude'],
                (float) $loc['longitude'],
                $currentLatitude,
                $currentLongitude,
            );

            if ($distance <= $radius) {
                $isInsideAnyLocation = true;
                break;
            }
        }

        if ($isInsideAnyLocation) {
            return [
                'key'      => 'location_inside_work_area',
                'label_ar'  => 'التواجد داخل نطاق العمل',
                'passed'    => true,
                'message'   => null,
            ];
        }

        return [
            'key'      => 'location_inside_work_area',
            'label_ar'  => 'التواجد داخل نطاق العمل',
            'passed'    => false,
            'message'   => 'You are outside the designated work area.',
        ];
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
    private function assertMaxScheduledDateOffset(string $userId, string $taskDate, array $settings): void
    {
        $mode = $settings['mode'] ?? 'max_task_date';

        if ($mode === 'max_task_date') {
            $maxDays = (int) ($settings['max_days'] ?? 30);
            $limit   = Carbon::today()->addDays($maxDays);
            $date    = Carbon::parse($taskDate)->startOfDay();

            if ($date->gt($limit)) {
                throw EmployeeTaskException::taskDateTooFarInFuture($maxDays);
            }

            return;
        }

        if ($mode === 'end_contract') {
            $user = User::with('companyUser.employmentContract.contractDurationUnit')
                ->find($userId);

            $contract = $user?->companyUser?->employmentContract;

            if ($contract === null || $contract->start_date === null) {
                return;
            }

            $endDate = Carbon::parse($contract->start_date);
            $duration = (int) $contract->contract_duration;

            $unit = $contract->contractDurationUnit;
            if ($unit !== null) {
                match ($unit->code ?? null) {
                    'day'   => $endDate->addDays($duration),
                    'month' => $endDate->addMonths($duration),
                    'year'  => $endDate->addYears($duration),
                    default => null,
                };
            }

            $taskDateCarbon = Carbon::parse($taskDate)->startOfDay();

            if ($taskDateCarbon->gt($endDate)) {
                throw EmployeeTaskException::taskDateExceedsContractEndDate();
            }
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

    /**
     * Check that the task location falls inside at least one of the custom
     * polygon areas drawn by the admin in the procedure-setting UI.
     *
     * @throws EmployeeTaskException
     */
    private function assertCustomLocationConditions(
        array $map,
        float $taskLatitude,
        float $taskLongitude,
    ): void {
        $cond = $map[InternalProcessCondition::InsideCustomLocations->value] ?? null;
        if (! $cond || ! ($cond['is_active'] ?? false)) {
            return;
        }

        $polygons = $cond['settings']['polygons'] ?? [];
        if (empty($polygons)) {
            return;
        }

        if (! GeoPolygon::isPointInAnyPolygon($taskLatitude, $taskLongitude, $polygons)) {
            throw EmployeeTaskException::outsideCustomLocations();
        }
    }

}
