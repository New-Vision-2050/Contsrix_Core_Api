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
            'management_hierarchy' => $this->resolveManagementHierarchyUsers($step, $createdByUserId),
            'specific_procedures' => $this->resolveSpecificProcedureUsers($step, $createdByUserId, $context),
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

    /**
     * @return list<string>
     */
    private function resolveManagementHierarchyUsers(ProcedureSettingStep $step, ?string $createdByUserId): array
    {
        $userId = $this->resolveManagerFromCreatorHierarchy($step, $createdByUserId);

        return $userId !== null ? [$userId] : [];
    }

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

    /**
     * @return list<string>
     */
    private function resolveSpecificProcedureUsers(
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context,
    ): array {
        $type = $step->action_taker_specific_procedure_type?->value;
        $id   = $step->action_taker_specific_procedure_id;

        if ($type === null || $id === null) {
            return [];
        }

        return match ($type) {
            'branch' => $this->resolveBranchManager((int) $id),
            'management' => $this->resolveManagementManager((int) $id),
            'job_title' => $this->resolveUsersByJobTitle($id),
            'job_role' => $this->resolveUsersByJobRole((int) $id),
            default => [],
        };
    }

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

    public function resolveManagerFromCreatorHierarchy(
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context = [],
    ): ?string {
        $hierarchyType = $step->action_taker_management_hierarchy_type?->value;

        if ($hierarchyType === null) {
            return null;
        }

        if ($hierarchyType === 'project_manager') {
            return $this->resolveProjectManager($step, $createdByUserId, $context);
        }

        if ($createdByUserId === null) {
            return $this->tryAlternative($step, $createdByUserId);
        }

        $creator = User::query()
            ->with('professionalData')
            ->find($createdByUserId);

        if ($creator === null) {
            return $this->tryAlternative($step, $createdByUserId);
        }

        $professionalData = $creator->professionalData;

        if ($professionalData === null) {
            return $this->tryAlternative($step, $createdByUserId);
        }

        $hierarchyId = null;
        if ($hierarchyType === 'branch_manager') {
            $hierarchyId = $professionalData->branch_id;
        } elseif ($hierarchyType === 'management_manager') {
            $hierarchyId = $professionalData->management_id;
        }

        if ($hierarchyId === null) {
            return $this->tryAlternative($step, $createdByUserId);
        }

        $hierarchy = ManagementHierarchy::query()->find($hierarchyId);

        if ($hierarchy === null || $hierarchy->manager_id === null) {
            return $this->tryAlternative($step, $createdByUserId);
        }

        return (string) $hierarchy->manager_id;
    }

    private function resolveProjectManager(
        ProcedureSettingStep $step,
        ?string $createdByUserId,
        array $context = [],
    ): ?string {
        $projectId = $context['project_id'] ?? null;

        if ($projectId !== null) {
            $project = \Modules\Project\ProjectManagement\Models\ProjectManagement::query()
                ->find($projectId);

            if ($project !== null && $project->manager_id !== null) {
                return (string) $project->manager_id;
            }
        }

        return $this->tryAlternative($step, $createdByUserId);
    }

    private function tryAlternative(ProcedureSettingStep $step, ?string $createdByUserId): ?string
    {
        $alternativeType = $step->action_taker_alternative_management_hierarchy_type?->value;

        if ($alternativeType === null || $createdByUserId === null) {
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

        $hierarchyId = null;
        if ($alternativeType === 'branch_manager') {
            $hierarchyId = $professionalData->branch_id;
        } elseif ($alternativeType === 'management_manager') {
            $hierarchyId = $professionalData->management_id;
        }

        if ($hierarchyId === null) {
            return null;
        }

        $hierarchy = ManagementHierarchy::query()->find($hierarchyId);

        if ($hierarchy === null || $hierarchy->manager_id === null) {
            return null;
        }

        return (string) $hierarchy->manager_id;
    }

    /**
     * Determine whether a rejection on a specific_procedure step should fail the process.
     * job_title  → yes (normal rejection)
     * job_role   → no  (rejection advances workflow)
     */
    public function rejectionShouldFailProcess(ProcedureSettingStep $step): bool
    {
        if ($step->action_taker_type?->value !== 'specific_procedures') {
            return true;
        }

        return $step->action_taker_specific_procedure_type?->value !== 'job_role';
    }
}
