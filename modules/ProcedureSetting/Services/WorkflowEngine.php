<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\ProcedureSetting\DTO\WorkflowStartResult;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Process\Services\ProcessWorkflowService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\User\Models\User;

final class WorkflowEngine
{
    public function __construct(
        private readonly ActionTakerResolver $resolver,
        private readonly ProcessWorkflowService $processService,
    ) {}

    public function resolveParentSetting(string $type, string $companyId, ?string $branchId): ?ProcedureSetting
    {
        $base = ProcedureSetting::query()
            ->whereNull('parent_id')
            ->where('type', $type)
            ->where('company_id', $companyId);

        if ($branchId !== null && $branchId !== '') {
            $byBranch = (clone $base)
                ->whereHas('workFlow.managementHierarchies', static function ($query) use ($branchId): void {
                    $query->where('management_hierarchies.id', $branchId);
                })
                ->orderBy('sort_order')
                ->first();

            if ($byBranch !== null) {
                return $byBranch;
            }
        }

        // Read-only fallback to the company default workflow. Do NOT use
        // WorkFlow::defaultForCompany() here: it firstOrCreate()s a row and this
        // path runs on preview/GET requests, which must not persist data.
        $default = WorkFlow::query()
            ->where('company_id', $companyId)
            ->where('name', 'default')
            ->where('type', $type)
            ->first();

        if ($default === null) {
            return null;
        }

        return (clone $base)
            ->where('work_flow_id', $default->id)
            ->orderBy('sort_order')
            ->first();
    }

    public function resolveSettingsForEntry(
        string $type,
        ?string $formKey,
        string $companyId,
        ?string $branchId,
    ): Collection {
        $parent = $this->resolveParentSetting($type, $companyId, $branchId);
        if ($parent === null) {
            return ProcedureSetting::query()->whereRaw('1 = 0')->get();
        }

        if ($formKey === null) {
            return ProcedureSetting::query()
                ->whereKey($parent->id)
                ->orderBy('sort_order')
                ->get();
        }

        return ProcedureSetting::query()
            ->where('parent_id', $parent->id)
            ->where('form', $formKey)
            ->whereNotNull('form')
            ->orderBy('sort_order')
            ->get();
    }

    public function previewResponsibles(
        string $type,
        ?string $formKey,
        string $companyId,
        ?string $branchId,
        ?string $createdByUserId,
        array $context = [],
    ): array {
        $settings = $this->resolveSettingsForEntry($type, $formKey, $companyId, $branchId);
        $setting = $settings->first();

        if ($setting === null) {
            return ['auto_approve' => true, 'step' => null, 'action_takers' => []];
        }

        $setting->load(['steps' => fn ($query) => $query->orderBy('step_order')->with(['actionTakers.user.companyUser.jobTitle'])]);

        if ($setting->steps->isEmpty()) {
            $descendant = $this->findFirstDescendantWithSteps($setting->id);
            if ($descendant !== null) {
                $setting = $descendant;
            }
        }

        return $this->computeApprovalResponsiblesForSetting($setting, $createdByUserId, $context);
    }

    public function startWorkflow(
        string $processableType,
        string $processableId,
        string $type,
        ?string $formKey,
        string $companyId,
        ?string $branchId,
        ?string $createdByUserId = null,
        array $context = [],
        ?array $metadata = null,
        ?ProcedureSetting $resolvedSetting = null,
    ): WorkflowStartResult {
        if ($resolvedSetting !== null) {
            $settings = new Collection([$resolvedSetting->load(['steps' => fn ($query) => $query->orderBy('step_order')])]);
        } else {
            $settings = $this->resolveSettingsForEntry($type, $formKey, $companyId, $branchId);
        }

        if ($settings->isEmpty()) {
            return new WorkflowStartResult(autoApprove: true, activeProcess: null);
        }

        $process = $this->processService->createProcessesFromSettings(
            $processableType,
            $processableId,
            $settings,
            $createdByUserId,
            $context,
            $metadata,
        );

        return $process === null
            ? new WorkflowStartResult(autoApprove: true, activeProcess: null)
            : new WorkflowStartResult(autoApprove: false, activeProcess: $process);
    }

    private function computeApprovalResponsiblesForSetting(ProcedureSetting $setting, ?string $createdByUserId, array $context): array
    {
        $firstStep = $setting->steps->first();

        if (! $firstStep) {
            return ['auto_approve' => true, 'step' => null, 'action_takers' => []];
        }

        $actionTakerType = $firstStep->action_taker_type?->value ?? 'specific_user';

        // Dynamic types: resolve all users via ActionTakerResolver (handles deputy_manager
        // multi-user, specific_procedures arrays, himself, and assigned_user).
        $dynamicTypes = ['management_hierarchy', 'specific_procedures', 'himself', 'assigned_user'];

        if (in_array($actionTakerType, $dynamicTypes, true)) {
            $resolvedUserIds = $this->resolver->resolveUsersForStep($firstStep, $createdByUserId, $context);

            if ($resolvedUserIds !== []) {
                $users = User::query()
                    ->whereIn('id', $resolvedUserIds)
                    ->with(['companyUser', 'companyUser.jobTitle'])
                    ->get(['id', 'name']);

                $actionTakers = [];
                foreach ($resolvedUserIds as $resolvedId) {
                    $user = $users->firstWhere('id', $resolvedId);
                    $actionTakers[] = [
                        'user_id'   => $resolvedId,
                        'name'      => $user?->name,
                        'photo'     => $user?->companyUser?->getFirstMedia('upload_user')?->getFullUrl(),
                        'job_title' => $user?->companyUser?->jobTitle?->name,
                    ];
                }

                return [
                    'auto_approve' => false,
                    'step' => [
                        'id'         => $firstStep->id,
                        'name'       => $firstStep->name,
                        'step_order' => $firstStep->step_order,
                    ],
                    'action_takers' => $actionTakers,
                ];
            }

            // Could not resolve any user → auto-approve.
            return ['auto_approve' => true, 'step' => null, 'action_takers' => []];
        }

        // specific_user: read from the actionTakers pivot relation.
        $actionTakers = [];
        foreach ($firstStep->actionTakers as $actionTaker) {
            $user = $actionTaker->relationLoaded('user') ? $actionTaker->user : null;
            $actionTakers[] = [
                'user_id'   => $actionTaker->user_id,
                'name'      => $user?->name,
                'photo'     => $user?->companyUser?->getFirstMedia('upload_user')?->getFullUrl(),
                'job_title' => $user?->companyUser?->jobTitle?->name,
            ];
        }

        return [
            'auto_approve' => $actionTakers === [],
            'step' => [
                'id'         => $firstStep->id,
                'name'       => $firstStep->name,
                'step_order' => $firstStep->step_order,
            ],
            'action_takers' => $actionTakers,
        ];
    }

    /**
     * Return a closure suitable for whereHas('employeeTask.processes', ...)
     * that filters for in-progress processes of the given type with at least
     * one pending step assigned to (or authorized for) the given user.
     *
     * Usage:
     *   $query->whereHas('employeeTask.processes',
     *       $engine->pendingProcessScopeForUser(
     *           ProcedureSettingType::ProjectNotificationTask->value,
     *           $userId,
     *       ));
     */
    public function pendingProcessScopeForUser(string $processableType, string $userId): \Closure
    {
        return function ($q) use ($processableType, $userId) {
            $q->where('processable_type', $processableType)
                ->where('status', ProcessStatus::InProgress)
                ->whereHas('steps', function ($q) use ($userId) {
                    $q->where('status', ProcessStepStatus::Pending)
                        ->where(function ($q) use ($userId) {
                            $q->where('assigned_user_id', $userId)
                                ->orWhereJsonContains('authorized_user_ids', $userId);
                        });
                });
        };
    }

    /**
     * Given a task model whose `processes` relation is already loaded,
     * return an array of pending-process descriptors for the given user.
     *
     * Each descriptor contains:
     *   - process_id, procedure_setting_id, form, mobile_inbox_action_key,
     *     pending_step_id, pending_step_order
     *
     * This centralises the logic previously duplicated in
     * ProjectNotificationService::resolvePendingProcessesForInbox.
     */
    public function resolvePendingProcessesForUser(Model $task, string $userId): array
    {
        if (! $task->relationLoaded('processes')) {
            return [];
        }

        $task->loadMissing('processes.procedureSetting');

        $result = [];
        foreach ($task->processes as $process) {
            if ($process->status !== ProcessStatus::InProgress) {
                continue;
            }

            $pendingStep = $process->steps->first(function ($step) use ($userId) {
                if ($step->status !== ProcessStepStatus::Pending) {
                    return false;
                }
                if ($step->assigned_user_id === $userId) {
                    return true;
                }
                $authorized = $step->authorized_user_ids ?? [];
                return in_array($userId, $authorized, true);
            });

            if ($pendingStep) {
                $formKey = $process->metadata['form'] ?? $process->procedureSetting?->form;
                $form = $formKey !== null ? InternalProcessForm::tryFrom($formKey) : null;

                $result[] = [
                    'process_id' => $process->id,
                    'procedure_setting_id' => $process->procedure_setting_id,
                    'form' => $formKey,
                    'mobile_inbox_action_key' => $form?->mobileInboxActionKey() ?? 'accept_reject',
                    'pending_step_id' => $pendingStep->id,
                    'pending_step_order' => $pendingStep->template_step_order,
                ];
            }
        }

        return $result;
    }

    /**
     * Check whether a processable entity has an active (in-progress) process
     * of the given type, optionally scoped to a specific procedure setting.
     */
    public function hasActiveProcess(string $processableType, string $processableId, ?string $procedureSettingId = null): bool
    {
        $query = Process::query()
            ->where('processable_type', $processableType)
            ->where('processable_id', $processableId)
            ->where('status', ProcessStatus::InProgress);

        if ($procedureSettingId !== null) {
            $query->where('procedure_setting_id', $procedureSettingId);
        }

        return $query->exists();
    }

    /**
     * Start a lifecycle workflow (update, site-status, fine, postponement, etc.)
     * for a processable entity.
     *
     * This centralises the pattern used by ProjectNotificationService::requestUpdate
     * and similar methods: resolve the procedure setting by form key (or use an
     * explicit one), build metadata, and call startWorkflow.
     *
     * @param  string  $processableType  e.g. ProcedureSettingType::ProjectNotificationTask->value
     * @param  string  $processableId    UUID of the EmployeeTaskRequest / entity
     * @param  string  $procedureType    Same as processableType for most cases
     * @param  string  $formKey          InternalProcessForm value
     * @param  string  $companyId
     * @param  ?string $branchId
     * @param  ?string $createdByUserId  The task creator / submitter
     * @param  array   $metadata         Process metadata (must include 'form')
     * @param  array   $context          Extra context (e.g. project_id)
     * @param  ?ProcedureSetting $resolvedSetting  Pre-resolved setting (skips lookup)
     * @return WorkflowStartResult
     */
    public function startLifecycleWorkflow(
        string $processableType,
        string $processableId,
        string $procedureType,
        string $formKey,
        string $companyId,
        ?string $branchId,
        ?string $createdByUserId,
        array $metadata,
        array $context = [],
        ?ProcedureSetting $resolvedSetting = null,
    ): WorkflowStartResult {
        if ($resolvedSetting === null) {
            $resolvedSetting = $this->resolveSettingsForEntry($procedureType, $formKey, $companyId, $branchId)->first();
        }

        if ($resolvedSetting === null) {
            return new WorkflowStartResult(autoApprove: true, activeProcess: null);
        }

        return $this->startWorkflow(
            processableType: $processableType,
            processableId: $processableId,
            type: $procedureType,
            formKey: $formKey,
            companyId: $companyId,
            branchId: $branchId,
            createdByUserId: $createdByUserId,
            context: $context,
            metadata: $metadata,
            resolvedSetting: $resolvedSetting,
        );
    }

    /**
     * Resolve a procedure setting for a lifecycle form, either from an explicit
     * ID (provided by the DTO) or by looking up the form key for the task's
     * company + branch.
     *
     * This centralises the pattern repeated across ProjectNotificationService
     * request* methods:
     *   $setting = $dto->internalProcedureSettingId !== null
     *       ? ProcedureSetting::find($dto->internalProcedureSettingId)
     *       : $this->procedureWorkflow->resolveInternalProcedureSettingByForm(...);
     *
     * @param  ?string $explicitSettingId  DTO-provided setting ID (takes priority)
     * @param  string  $procedureType      e.g. ProcedureSettingType::ProjectNotificationTask->value
     * @param  string  $formKey            InternalProcessForm value
     * @param  string  $companyId
     * @param  ?string $branchId
     * @return ?ProcedureSetting
     */
    public function resolveLifecycleSetting(
        ?string $explicitSettingId,
        string $procedureType,
        string $formKey,
        string $companyId,
        ?string $branchId,
    ): ?ProcedureSetting {
        if ($explicitSettingId !== null) {
            return ProcedureSetting::query()->find($explicitSettingId);
        }

        return $this->resolveSettingsForEntry($procedureType, $formKey, $companyId, $branchId)->first();
    }

    private function findFirstDescendantWithSteps(string $parentId): ?ProcedureSetting
    {
        $eager = ['steps' => fn ($query) => $query->orderBy('step_order')->with(['actionTakers.user.companyUser.jobTitle'])];

        $internalChildren = ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->whereNotNull('form')
            ->with($eager)
            ->orderBy('sort_order')
            ->get();

        foreach ($internalChildren as $child) {
            if ($child->steps->isNotEmpty()) {
                return $child;
            }

            $descendant = $this->findFirstDescendantWithSteps($child->id);
            if ($descendant !== null) {
                return $descendant;
            }
        }

        $otherChildren = ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->whereNull('form')
            ->with($eager)
            ->orderBy('sort_order')
            ->get();

        foreach ($otherChildren as $child) {
            if ($child->steps->isNotEmpty()) {
                return $child;
            }

            $descendant = $this->findFirstDescendantWithSteps($child->id);
            if ($descendant !== null) {
                return $descendant;
            }
        }

        return null;
    }
}
