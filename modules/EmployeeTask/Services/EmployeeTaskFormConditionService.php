<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Modules\EmployeeTask\Conditions\EmployeeTaskExceptionResolver;
use Modules\ProcedureSetting\Conditions\ConditionEvaluatorRegistry;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluationService;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;

/**
 * Evaluates InternalProcessForm conditions stored on child ProcedureSetting records.
 *
 * Uses a registry-driven dispatch pattern: each condition key is handled by
 * a dedicated ConditionEvaluator class registered in the service provider.
 * Adding a new condition = create evaluator class + register it. No changes
 * to this service are needed (Open/Closed Principle).
 *
 * Active forms:
 *   createTask — rich-array format with mode-aware AllowDuringShift (shift | specific_time)
 *   startTask  — holiday gating
 *   endTask    — no conditions (no-op)
 */
final class EmployeeTaskFormConditionService
{
    public function __construct(
        private readonly ConditionEvaluatorRegistry   $registry,
        private readonly ConditionEvaluationService   $evaluationService,
        private readonly EmployeeTaskExceptionResolver $resolver,
        private readonly WorkflowEngine               $engine,
    ) {}

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Check all createTask conditions by dispatching to registered evaluators.
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
        ?string $formKey = null,
    ): void {
        $map = $this->resolveConditionMap(
            $formKey ?? InternalProcessForm::CreateTask->value,
            $companyId,
            $branchId,
        );

        if ($map === null) {
            return;
        }

        // Dashboard-created project notifications are submitted by an admin
        // on behalf of the employee, so the employee's real-time context
        // (current shift, current GPS, today's holiday status) is unavailable.
        // Skip the conditions that depend on it; task-data validations
        // (duration, date offset, custom locations) remain enforced.
        $map = $this->skipRealtimeConditionsForDashboardNotification(
            $map,
            $formKey ?? InternalProcessForm::CreateTask->value,
            $currentLatitude,
            $currentLongitude,
        );

        $ctx = new ConditionContext(
            userId: $userId,
            companyId: $companyId,
            branchId: $branchId,
            currentLatitude: $currentLatitude,
            currentLongitude: $currentLongitude,
            taskLatitude: $taskLatitude,
            taskLongitude: $taskLongitude,
            durationHours: $durationHours,
            taskDate: $taskDate,
        );

        $this->evaluationService->evaluateAndThrow($this->registry, $map, $ctx, $this->resolver);
    }

    /**
     * Evaluate precondition-type (form_group = 'precondition') createTask conditions
     * and return individual pass/fail results without throwing.
     *
     * ALWAYS returns all precondition results so the mobile app can show a fixed
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
        $map = $this->resolveConditionMap(
            InternalProcessForm::CreateTask->value,
            $companyId,
            $branchId,
        );

        $map = $map ?? [];
        $ctx = new ConditionContext(
            userId: $userId,
            companyId: $companyId,
            branchId: $branchId,
            currentLatitude: $currentLatitude,
            currentLongitude: $currentLongitude,
        );

        return $this->evaluationService->evaluateForResults(
            $this->registry,
            $map,
            $ctx,
            'precondition',
        );
    }

    /**
     * Return active in_form conditions for the createTask form so the mobile
     * app can display them as hints/constraints before the employee submits.
     *
     * Output is NORMALIZED — every item has the same shape:
     *   key, label_ar, is_active, mode, constraints
     *
     * The preview is generated automatically from each condition's
     * settingsSchema() via InternalProcessCondition::toPreview().
     * Adding a new in_form condition with a settingsSchema() automatically
     * makes it appear here — no match block to update.
     *
     * @return list<array{key: string, label_ar: string, is_active: true, mode: ?string, constraints: array}>
     */
    public function getInFormConditionsPreview(
        string  $companyId,
        ?string $branchId,
    ): array {
        $map = $this->resolveConditionMap(
            InternalProcessForm::CreateTask->value,
            $companyId,
            $branchId,
        );

        if ($map === null) {
            return [];
        }

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

            $preview = $condEnum->toPreview($item['settings'] ?? []);

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

        $formKey = InternalProcessForm::StartTask->value;

        $map = $this->resolveConditionMap(
            $formKey,
            $companyId,
            $branchId,
        );

        if ($map === null) {
            return;
        }

        $ctx = new ConditionContext(
            userId: (string) $user->id,
            companyId: $companyId,
            branchId: $branchId,
            currentLatitude: $latitude,
            currentLongitude: $longitude,
        );

        $this->evaluationService->evaluateAndThrow($this->registry, $map, $ctx, $this->resolver);
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
     * Conditions that depend on the employee's real-time context (current
     * shift window, current GPS location, today's holiday status) cannot be
     * evaluated when an admin creates a project notification from the dashboard.
     *
     * For CreateProjectNotificationTask the system only enforces the
     * InsideCustomLocations condition (task location must be inside configured
     * custom polygons). All other conditions are omitted from the enum definition,
     * so this helper only removes them if they somehow appear in the map.
     *
     * Normal employee task creation (CreateTask form) is unaffected.
     *
     * @param array<string, array{key: string, is_active: bool, sort_order: int, settings: array}> $map
     * @return array<string, array{key: string, is_active: bool, sort_order: int, settings: array}>
     */
    private function skipRealtimeConditionsForDashboardNotification(
        array $map,
        string $formKey,
        ?float $currentLatitude,
        ?float $currentLongitude,
    ): array {
        if ($formKey !== InternalProcessForm::CreateProjectNotificationTask->value) {
            return $map;
        }

        // If current GPS is provided, keep AllowOutsideShift so the evaluator
        // can still verify the work-area radius.
        $hasGps = $currentLatitude !== null && $currentLongitude !== null;

        $realtimeConditions = [
            InternalProcessCondition::AllowDuringShift,
            InternalProcessCondition::AllowOnHolidays,
        ];

        if (! $hasGps) {
            $realtimeConditions[] = InternalProcessCondition::AllowOutsideShift;
        }

        foreach ($realtimeConditions as $condition) {
            unset($map[$condition->value]);
        }

        return $map;
    }

    /**
     * Resolve + normalize stored conditions into a keyed map.
     * Returns null when no setting or conditions are empty → check passes silently.
     *
     * @return array<string, array{key: string, is_active: bool, sort_order: int, settings: array}>|null
     */
    private function resolveConditionMap(
        string  $formKey,
        string  $companyId,
        ?string $branchId,
    ): ?array {
        $procedureType = $this->procedureTypeForForm($formKey);

        $settings = $this->engine->resolveSettingsForEntry(
            $procedureType,
            $formKey,
            $companyId,
            $branchId,
        );

        $setting = $settings->first();

        if ($setting === null || empty($setting->conditions)) {
            return null;
        }

        return $this->indexConditions($setting->conditions);
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

    private function procedureTypeForForm(string $formKey): string
    {
        try {
            return InternalProcessForm::from($formKey)->procedureSettingType()->value;
        } catch (\ValueError) {
            return ProcedureSettingType::EmployeeTask->value;
        }
    }

}
