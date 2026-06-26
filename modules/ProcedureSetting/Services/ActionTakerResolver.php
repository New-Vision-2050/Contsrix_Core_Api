<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Illuminate\Support\Facades\DB;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

class ActionTakerResolver
{
    /**
     * Resolve the list of user IDs that can act on a given procedure step.
     *
     * @return list<string>
     */
    public function resolveUsersForStep(
        ProcedureSettingStep $step,
        ?string $createdByUserId = null,
        array $context = [],
    ): array {
        $actionTakerType = $step->action_taker_type?->value ?? 'specific_user';

        return match ($actionTakerType) {
            'management_hierarchy' => $this->resolveManagementHierarchyUsers($step, $createdByUserId, $context),
            'specific_procedures'  => $this->resolveSpecificProcedureUsers($step, $createdByUserId, $context),

            // The submitter themselves is the action taker.
            'himself' => $createdByUserId !== null ? [$createdByUserId] : [],

            // The entity assigned to the task/request (e.g. EmployeeTaskRequest.user_id).
            'assigned_user' => $createdByUserId !== null ? [$createdByUserId] : [],

            default => $this->resolveSpecificUserIds($step),
        };
    }

    /**
     * Resolve a single assigned user ID for process snapshot creation.
     * Returns the first user from the resolved list, or null if empty.
     */
    public function resolveAssignedUserId(
        ProcedureSettingStep $step,
        ?string $createdByUserId = null,
        array $context = [],
    ): ?string {
        $users = $this->resolveUsersForStep($step, $createdByUserId, $context);

        return $users[0] ?? null;
    }

    // -------------------------------------------------------------------------
    // Management-hierarchy resolution
    // -------------------------------------------------------------------------

    /**
     * For `deputy_manager` type: returns BOTH the branch/management manager AND
     * all of their deputy managers so either one can act on the step.
     *
     * For all other hierarchy types: returns a single-element array (or empty).
     *
     * @return list<string>
     */
    private function resolveManagementHierarchyUsers(
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context = [],
    ): array {
        // ── New canonical format: action_taker_management_hierarchies ───────
        // Array of {action_taker_management_hierarchy_type, is_Deputy_Director} objects.
        // Iterate each row, resolve the manager (and deputies if flagged), merge all.
        $hierarchies = $step->action_taker_management_hierarchies;
        if (!empty($hierarchies)) {
            return $this->resolveFromManagementHierarchiesArray(
                $hierarchies,
                $step,
                $createdByUserId,
                $context,
            );
        }

        // ── Legacy fallback: single type + alternatives ─────────────────────
        $hierarchyType = $step->action_taker_management_hierarchy_type?->value;

        if ($hierarchyType === 'deputy_manager') {
            return $this->resolveManagerAndDeputies($step, $createdByUserId);
        }

        $userId = $this->resolveManagerFromCreatorHierarchy($step, $createdByUserId, $context);

        return $userId !== null ? [$userId] : [];
    }

    /**
     * Resolve users from the new action_taker_management_hierarchies array format.
     * Each row: {action_taker_management_hierarchy_type: string, is_Deputy_Director: bool}
     * Returns merged + de-duplicated user IDs from all rows.
     *
     * @param list<array{action_taker_management_hierarchy_type: string, is_Deputy_Director: bool}> $hierarchies
     * @return list<string>
     */
    private function resolveFromManagementHierarchiesArray(
        array $hierarchies,
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context = [],
    ): array {
        $users = [];

        foreach ($hierarchies as $row) {
            $type = $row['action_taker_management_hierarchy_type'] ?? '';
            $isDeputy = (bool) ($row['is_Deputy_Director'] ?? false);

            if ($type === '') {
                continue;
            }

            // Resolve the manager for this hierarchy type.
            $managerId = $this->resolveManagerByType($type, $step, $createdByUserId, $context);

            if ($managerId !== null) {
                $users[$managerId] = true;
            }

            // If deputy flag is set, also include all deputy managers.
            if ($isDeputy) {
                $deputyIds = $this->resolveDeputyManagersForType($type, $createdByUserId);
                foreach ($deputyIds as $deputyId) {
                    $users[$deputyId] = true;
                }
            }
        }

        return array_keys($users);
    }

    /**
     * Resolve a single manager user ID for a given hierarchy type.
     * Used by the new array format resolver.
     */
    private function resolveManagerByType(
        string $hierarchyType,
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context = [],
    ): ?string {
        if ($hierarchyType === 'project_manager') {
            // Resolve project manager directly — do NOT call tryAlternatives
            // (which reads legacy fields). If unresolvable, return null so the
            // caller loop moves to the next row in the array.
            $projectId = $context['project_id'] ?? null;
            if ($projectId !== null) {
                $project = \Modules\Project\ProjectManagement\Models\ProjectManagement::query()
                    ->withoutGlobalScopes()
                    ->find($projectId);
                if ($project !== null && $project->manager_id !== null) {
                    return (string) $project->manager_id;
                }
            }
            return null;
        }

        if ($createdByUserId === null) {
            return null;
        }

        $creator = User::query()
            ->with('professionalData')
            ->find($createdByUserId);

        if ($creator === null) {
            return null;
        }

        $professionalData = $creator->professionalData;
        if ($professionalData === null) {
            return null;
        }

        return $this->resolveByHierarchyType($hierarchyType, $professionalData);
    }

    /**
     * Resolve all deputy manager user IDs for a given hierarchy type.
     * Looks up the creator's branch/management hierarchy and returns deputy managers.
     *
     * @return list<string>
     */
    private function resolveDeputyManagersForType(string $hierarchyType, ?string $createdByUserId): array
    {
        if ($createdByUserId === null) {
            return [];
        }

        $creator = User::query()->with('professionalData')->find($createdByUserId);
        if ($creator === null) {
            return [];
        }

        $professionalData = $creator->professionalData;
        if ($professionalData === null) {
            return [];
        }

        $hierarchyId = match ($hierarchyType) {
            'branch_manager'     => $professionalData->branch_id ?? null,
            'management_manager' => $professionalData->management_id ?? null,
            // project_manager has no hierarchy-level deputies.
            'project_manager'    => null,
            default              => $professionalData->branch_id ?? $professionalData->management_id ?? null,
        };

        if ($hierarchyId === null) {
            return [];
        }

        $hierarchy = ManagementHierarchy::query()
            ->with('detail.deputyManagerRelations')
            ->find($hierarchyId);

        if ($hierarchy === null) {
            return [];
        }

        $deputyIds = [];
        $detail = $hierarchy->detail;
        if ($detail !== null) {
            foreach ($detail->deputyManagerRelations as $relation) {
                if ($relation->deputy_manager_id !== null) {
                    $deputyIds[(string) $relation->deputy_manager_id] = true;
                }
            }
        }

        return array_keys($deputyIds);
    }

    /**
     * Resolve the branch/management MANAGER plus ALL deputy managers of the
     * creator's hierarchy node. Every resolved user is authorized to act;
     * whichever acts first advances the step.
     *
     * @return list<string>
     */
    private function resolveManagerAndDeputies(
        ProcedureSettingStep $step,
        ?string $createdByUserId,
    ): array {
        if ($createdByUserId === null) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        $creator = User::query()->with('professionalData')->find($createdByUserId);

        if ($creator === null) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        $professionalData = $creator->professionalData;

        if ($professionalData === null) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        // Prefer the creator's branch; fall back to their management department.
        $hierarchyId = $professionalData->branch_id ?? $professionalData->management_id ?? null;

        if ($hierarchyId === null) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        $hierarchy = ManagementHierarchy::query()
            ->with('detail.deputyManagerRelations')
            ->find($hierarchyId);

        if ($hierarchy === null) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        $users = [];

        // Primary: the hierarchy manager.
        if ($hierarchy->manager_id !== null) {
            $users[(string) $hierarchy->manager_id] = true;
        }

        // All deputy managers via the detail pivot.
        $detail = $hierarchy->detail;
        if ($detail !== null) {
            foreach ($detail->deputyManagerRelations as $relation) {
                if ($relation->deputy_manager_id !== null) {
                    $users[(string) $relation->deputy_manager_id] = true;
                }
            }
        }

        $result = array_keys($users);

        if ($result === []) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Specific-procedures resolution (parallel arrays)
    // -------------------------------------------------------------------------

    /**
     * Resolves users for all specific-procedure targets (array of type+id pairs).
     * Results from all targets are merged and de-duplicated.
     *
     * @return list<string>
     */
    private function resolveSpecificProcedureUsers(
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context,
    ): array {
        $types = (array) ($step->action_taker_specific_procedure_type ?? []);
        $ids   = (array) ($step->action_taker_specific_procedure_id   ?? []);

        if ($types === [] || $ids === []) {
            return [];
        }

        $userIds = [];
        foreach ($types as $index => $type) {
            $id = $ids[$index] ?? null;
            if ($type === null || $id === null) {
                continue;
            }

            $resolved = match ((string) $type) {
                'branch'     => $this->resolveBranchManager((int) $id),
                'management' => $this->resolveManagementManager((int) $id),
                'job_title'  => $this->resolveUsersByJobTitle((string) $id),
                'job_role'   => $this->resolveUsersByJobRole((int) $id),
                default      => [],
            };

            foreach ($resolved as $uid) {
                $userIds[$uid] = $uid;
            }
        }

        return array_values($userIds);
    }

    // -------------------------------------------------------------------------
    // Specific-user resolution
    // -------------------------------------------------------------------------

    /**
     * @return list<string>
     */
    private function resolveSpecificUserIds(ProcedureSettingStep $step): array
    {
        if (! $step->relationLoaded('actionTakers')) {
            $step->load('actionTakers');
        }

        return $step->actionTakers
            ->pluck('user_id')
            ->filter()
            ->map(static fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Management-hierarchy single-user resolution (used for assigned_user_id)
    // -------------------------------------------------------------------------

    /**
     * Resolve the PRIMARY (single) manager for a management_hierarchy step.
     *
     * For `deputy_manager` type this returns the branch/management manager_id
     * (the primary slot in the snapshot). Deputy managers are included in
     * `authorized_user_ids` via `resolveManagementHierarchyUsers`.
     *
     * Used by:
     *  - `resolveManagementHierarchyUsers` for non-deputy types
     *  - `ProcedureWorkflowService::assertIsActionTaker` (non-Process path)
     *  - `WorkflowEngine::computeApprovalResponsiblesForSetting`
     */
    public function resolveManagerFromCreatorHierarchy(
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context = [],
    ): ?string {
        // ── New canonical format: iterate rows in order ───────────────────
        // First row that successfully resolves a manager wins. If a row can't
        // resolve (e.g. project_manager without project_id), skip it and try
        // the next row. Do NOT call tryAlternatives (which reads legacy fields).
        $hierarchies = $step->action_taker_management_hierarchies;
        if (!empty($hierarchies)) {
            // Preload creator once for all rows.
            $creator = null;
            $professionalData = null;
            if ($createdByUserId !== null) {
                $creator = User::query()
                    ->with('professionalData')
                    ->find($createdByUserId);
                $professionalData = $creator?->professionalData;
            }

            foreach ($hierarchies as $row) {
                $rowType = $row['action_taker_management_hierarchy_type'] ?? '';
                if ($rowType === '') {
                    continue;
                }

                // project_manager resolves from context, not professionalData.
                if ($rowType === 'project_manager') {
                    $projectId = $context['project_id'] ?? null;
                    if ($projectId !== null) {
                        $project = \Modules\Project\ProjectManagement\Models\ProjectManagement::query()
                            ->withoutGlobalScopes()
                            ->find($projectId);
                        if ($project !== null && $project->manager_id !== null) {
                            return (string) $project->manager_id;
                        }
                    }
                    // No project_id or project not found → skip to next row.
                    continue;
                }

                // Other types need professionalData.
                if ($professionalData === null) {
                    continue;
                }

                $resolved = $this->resolveByHierarchyType($rowType, $professionalData);
                if ($resolved !== null) {
                    return $resolved;
                }
            }

            return null;
        }

        // ── Legacy fallback ─────────────────────────────────────────────────
        $hierarchyType = $step->action_taker_management_hierarchy_type?->value;

        if ($hierarchyType === null) {
            return null;
        }

        if ($hierarchyType === 'project_manager') {
            return $this->resolveProjectManager($step, $createdByUserId, $context);
        }

        if ($createdByUserId === null) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        $creator = User::query()
            ->with('professionalData')
            ->find($createdByUserId);

        if ($creator === null) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        $professionalData = $creator->professionalData;

        if ($professionalData === null) {
            return $this->tryAlternatives($step, $createdByUserId);
        }

        $resolved = $this->resolveByHierarchyType($hierarchyType, $professionalData);

        if ($resolved !== null) {
            return $resolved;
        }

        return $this->tryAlternatives($step, $createdByUserId);
    }

    private function resolveProjectManager(
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context = [],
    ): ?string {
        $projectId = $context['project_id'] ?? null;

        if ($projectId !== null) {
            $project = \Modules\Project\ProjectManagement\Models\ProjectManagement::query()
                ->withoutGlobalScopes()
                ->find($projectId);

            if ($project !== null && $project->manager_id !== null) {
                return (string) $project->manager_id;
            }
        }

        return $this->tryAlternatives($step, $createdByUserId);
    }

    /**
     * Tries each alternative hierarchy type in order, returning the first non-null user.
     * `action_taker_alternative_management_hierarchy_type` is a JSON array of type strings.
     */
    private function tryAlternatives(ProcedureSettingStep $step, ?string $createdByUserId): ?string
    {
        $alternatives = (array) ($step->action_taker_alternative_management_hierarchy_type ?? []);

        if ($alternatives === [] || $createdByUserId === null) {
            return null;
        }

        $creator = User::query()
            ->with('professionalData')
            ->find($createdByUserId);

        if ($creator === null) {
            return null;
        }

        $professionalData = $creator->professionalData;

        if ($professionalData === null) {
            return null;
        }

        foreach ($alternatives as $alternativeType) {
            $resolved = $this->resolveByHierarchyType((string) $alternativeType, $professionalData);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Resolve a single user ID from the creator's professional data for a given
     * hierarchy type. Returns null when unresolvable.
     *
     * For `deputy_manager` this returns the first deputy manager (used in the
     * alternative fallback chain where only one user is needed).
     */
    private function resolveByHierarchyType(string $hierarchyType, object $professionalData): ?string
    {
        $hierarchyId = match ($hierarchyType) {
            'branch_manager'     => $professionalData->branch_id ?? null,
            'management_manager' => $professionalData->management_id ?? null,
            // For deputy_manager in a fallback: use branch first, then management.
            'deputy_manager'     => $professionalData->branch_id ?? $professionalData->management_id ?? null,
            default              => null,
        };

        if ($hierarchyId === null) {
            return null;
        }

        $hierarchy = ManagementHierarchy::query()->find($hierarchyId);

        if ($hierarchy === null) {
            return null;
        }

        if ($hierarchyType === 'deputy_manager') {
            // In fallback mode: return the first deputy manager.
            $detail = $hierarchy->detail()->first();
            if ($detail !== null) {
                $deputyRelation = $detail->deputyManagerRelations()->first();
                if ($deputyRelation !== null && $deputyRelation->deputy_manager_id !== null) {
                    return (string) $deputyRelation->deputy_manager_id;
                }
            }

            return null;
        }

        if ($hierarchy->manager_id === null) {
            return null;
        }

        return (string) $hierarchy->manager_id;
    }

    // -------------------------------------------------------------------------
    // Specific-procedure helpers
    // -------------------------------------------------------------------------

    /**
     * @return list<string>
     */
    private function resolveBranchManager(int $branchId): array
    {
        $managerId = ManagementHierarchy::query()
            ->where('id', $branchId)
            ->where('type', 'branch')
            ->value('manager_id');

        return $managerId !== null ? [(string) $managerId] : [];
    }

    /**
     * @return list<string>
     */
    private function resolveManagementManager(int $managementId): array
    {
        $managerId = ManagementHierarchy::query()
            ->where('id', $managementId)
            ->where('type', 'management')
            ->value('manager_id');

        return $managerId !== null ? [(string) $managerId] : [];
    }

    /**
     * @return list<string>
     */
    private function resolveUsersByJobTitle(string $jobTitleId): array
    {
        return User::query()
            ->whereHas('professionalData', static function ($query) use ($jobTitleId) {
                $query->where('job_title_id', $jobTitleId);
            })
            ->pluck('id')
            ->map(static fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function resolveUsersByJobRole(int $roleValue): array
    {
        $type = $roleValue === 1 ? 'management' : 'branch';

        return ManagementHierarchy::query()
            ->where('type', $type)
            ->whereNotNull('manager_id')
            ->pluck('manager_id')
            ->unique()
            ->map(static fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Rejection rule
    // -------------------------------------------------------------------------

    /**
     * Determine whether a rejection on a specific_procedure step should fail the process.
     * job_title  → yes (normal rejection)
     * job_role   → no  (rejection advances workflow)
     *
     * When the step has multiple specific-procedure types, rejection fails only
     * if NO target is job_role.
     */
    public function rejectionShouldFailProcess(ProcedureSettingStep $step): bool
    {
        if ($step->action_taker_type?->value !== 'specific_procedures') {
            return true;
        }

        $types = (array) ($step->action_taker_specific_procedure_type ?? []);

        if ($types === []) {
            return true;
        }

        return ! in_array('job_role', $types, true);
    }
}
