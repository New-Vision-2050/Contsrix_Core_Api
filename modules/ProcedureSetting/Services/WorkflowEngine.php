<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\ProcedureSetting\DTO\WorkflowStartResult;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Process\Services\ProcessWorkflowService;
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
    ): WorkflowStartResult {
        $settings = $this->resolveSettingsForEntry($type, $formKey, $companyId, $branchId);
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
