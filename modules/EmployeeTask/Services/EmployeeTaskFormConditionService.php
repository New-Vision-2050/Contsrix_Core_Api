<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

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
 * Called before any task lifecycle action (create, end) to gate the action
 * based on the employee's current attendance shift status or location.
 *
 * Condition keys are the snake_case values of InternalProcessCondition:
 *   allow_during_shift, allow_outside_shift, allow_on_holidays,
 *   can_exit_outside_location
 *
 * When no procedure setting is found for the form, or its conditions are empty,
 * the check passes silently (no configuration = no restriction).
 */
final class EmployeeTaskFormConditionService
{
    public function __construct(
        private readonly AttendanceConstraintService $attendanceConstraintService,
        private readonly EmployeeTaskLocationService  $locationService,
        private readonly ProcedureWorkflowService     $workflow,
    ) {}

    /**
     * Check shift/holiday conditions for the createTask form.
     *
     * @throws EmployeeTaskException when conditions are not met
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

        $this->assertShiftConditions($conditions, $userId);
    }

    /**
     * Check location condition for the endTask form.
     *
     * @throws EmployeeTaskException when conditions are not met
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

        $canExitOutside = (bool) ($conditions[InternalProcessCondition::CanExitOutsideLocation->value] ?? true);

        if (! $canExitOutside) {
            $inLocation = $this->locationService->isWithinTaskRadius($task, $latitude, $longitude);
            if (! $inLocation) {
                throw EmployeeTaskException::cannotEndTaskOutsideLocation();
            }
        }
    }

    // ─── private ─────────────────────────────────────────────────────────────

    /**
     * Resolve the conditions array from the child ProcedureSetting for the given form.
     * Returns null when no setting or no conditions are configured.
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

        if ($setting === null) {
            return null;
        }

        $conditions = $setting->conditions;

        if (empty($conditions)) {
            return null;
        }

        return $conditions;
    }

    /**
     * Evaluate AllowDuringShift / AllowOutsideShift / AllowOnHolidays against
     * the employee's current attendance work-rules.
     */
    private function assertShiftConditions(array $conditions, string $userId): void
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

        $workRules = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user);

        $isHoliday     = (bool) ($workRules['is_holiday'] ?? false);
        $isDuringShift = ($workRules['current_work_period'] ?? null) !== null;

        if ($isHoliday) {
            $allowOnHolidays = (bool) ($conditions[InternalProcessCondition::AllowOnHolidays->value] ?? true);
            if (! $allowOnHolidays) {
                throw EmployeeTaskException::notAllowedOnHolidays();
            }
            // Shift conditions do not apply on holidays.
            return;
        }

        if ($isDuringShift) {
            $allowDuringShift = (bool) ($conditions[InternalProcessCondition::AllowDuringShift->value] ?? true);
            if (! $allowDuringShift) {
                throw EmployeeTaskException::notAllowedDuringShift();
            }
        } else {
            $allowOutsideShift = (bool) ($conditions[InternalProcessCondition::AllowOutsideShift->value] ?? true);
            if (! $allowOutsideShift) {
                throw EmployeeTaskException::notAllowedOutsideShift();
            }
        }
    }
}
