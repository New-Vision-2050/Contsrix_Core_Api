<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\ProcedureSetting\Repositories\ProcedureSettingRepository;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Repositories\ProjectProcedureRepository;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Ramsey\Uuid\Uuid;

class ProjectProcedureService
{
    public const PROCEDURE_TYPE = ProjectProcedureSetting::PROCEDURE_TYPE;

    public function __construct(
        private readonly ProjectProcedureRepository $repository,
        private readonly ProcedureSettingRepository $procedureSettingRepository,
    ) {}

    public function list(string $projectId, ?string $parentProcedureSettingId = null): Collection
    {
        $project = $this->findOwnedProjectOrFail($projectId);

        if ($parentProcedureSettingId !== null) {
            $this->findProjectProcedureParentOrFail($project, $parentProcedureSettingId);
        }

        return $this->repository->listForProject($project->id, self::PROCEDURE_TYPE, $parentProcedureSettingId);
    }

    public function get(
        string $projectId,
        string $procedureSettingId,
        ?string $parentProcedureSettingId = null
    ): ProjectProcedureSetting
    {
        $project = $this->findOwnedProjectOrFail($projectId);

        return $this->repository->findForProject(
            $project->id,
            $procedureSettingId,
            self::PROCEDURE_TYPE,
            $parentProcedureSettingId
        );
    }

    public function create(
        string $projectId,
        array $procedureData,
        array $metadata,
        ?string $parentProcedureSettingId = null
    ): ProjectProcedureSetting
    {
        $project = $this->findOwnedProjectOrFail($projectId);
        $receiverCompanyId = $metadata['receiver_company_id'] ?? null;

        if ($receiverCompanyId) {
            $this->assertReceiverCompanyIsSharedWithProject($project, (string) $receiverCompanyId);
        }

        return DB::transaction(function () use (
            $project,
            $procedureData,
            $metadata,
            $parentProcedureSettingId
        ): ProjectProcedureSetting {
            if ($parentProcedureSettingId !== null) {
                $parent = $this->findProjectProcedureParentOrFail($project, $parentProcedureSettingId);
                $workFlowId = $parent->work_flow_id;
            } else {
                $workFlow = $this->projectWorkFlow($project);
                $parent = $this->projectProcedureParent($project, $workFlow);
                $workFlowId = $workFlow->id;
            }

            $procedureSetting = $this->procedureSettingRepository->createProcedureSetting(
                array_merge($this->procedureSettingPayload($project, $procedureData), [
                    'work_flow_id' => $workFlowId,
                    'parent_id' => $parent->id,
                ])
            );

            return $this->repository->createProjectProcedure(array_merge($metadata, [
                'company_id' => $project->company_id,
                'project_id' => $project->id,
                'procedure_setting_id' => $procedureSetting->id,
            ]));
        });
    }

    public function update(
        string $projectId,
        string $procedureSettingId,
        array $procedureData,
        array $metadata,
        ?string $parentProcedureSettingId = null
    ): ProjectProcedureSetting {
        $project = $this->findOwnedProjectOrFail($projectId);
        $projectProcedure = $this->repository->findForProject(
            $project->id,
            $procedureSettingId,
            self::PROCEDURE_TYPE,
            $parentProcedureSettingId
        );
        $receiverCompanyId = $metadata['receiver_company_id'] ?? null;

        if ($receiverCompanyId) {
            $this->assertReceiverCompanyIsSharedWithProject($project, (string) $receiverCompanyId);
        }

        return DB::transaction(function () use ($projectProcedure, $procedureData, $metadata): ProjectProcedureSetting {
            if ($procedureData !== []) {
                $this->procedureSettingRepository->updateProcedureSetting(
                    Uuid::fromString($projectProcedure->procedure_setting_id),
                    $procedureData
                );
            }

            if ($metadata !== []) {
                $projectProcedure = $this->repository->updateProjectProcedure($projectProcedure, $metadata);
            }

            return $this->repository->loadRelations($projectProcedure->refresh());
        });
    }

    public function delete(
        string $projectId,
        string $procedureSettingId,
        ?string $parentProcedureSettingId = null
    ): void
    {
        $project = $this->findOwnedProjectOrFail($projectId);
        $projectProcedure = $this->repository->findForProject(
            $project->id,
            $procedureSettingId,
            self::PROCEDURE_TYPE,
            $parentProcedureSettingId
        );

        DB::transaction(function () use ($projectProcedure): void {
            $this->procedureSettingRepository->deleteProcedureSetting(
                Uuid::fromString($projectProcedure->procedure_setting_id)
            );
        });
    }

    private function findOwnedProjectOrFail(string $projectId): ProjectManagement
    {
        return ProjectManagement::withoutGlobalScopes()
            ->where('id', $projectId)
            ->where('company_id', tenant('id'))
            ->firstOrFail();
    }

    private function projectWorkFlow(ProjectManagement $project): WorkFlow
    {
        $query = WorkFlow::query()
            ->withoutGlobalScopes()
            ->where('company_id', $project->company_id)
            ->where('project_id', $project->id)
            ->where('type', self::PROCEDURE_TYPE);

        $existing = (clone $query)
            ->where('name', $this->projectWorkFlowName($project))
            ->first();

        if ($existing instanceof WorkFlow) {
            return $existing;
        }

        $existing = $query->orderBy('created_at')->orderBy('id')->first();

        if ($existing instanceof WorkFlow) {
            return $existing;
        }

        return WorkFlow::query()
            ->withoutGlobalScopes()
            ->create([
                'company_id' => $project->company_id,
                'project_id' => $project->id,
                'name' => $this->projectWorkFlowName($project),
                'type' => self::PROCEDURE_TYPE,
            ]);
    }

    private function projectProcedureParent(ProjectManagement $project, WorkFlow $workFlow): ProcedureSetting
    {
        $existing = ProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $project->company_id)
            ->where('work_flow_id', $workFlow->id)
            ->where('type', self::PROCEDURE_TYPE)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->first();

        if ($existing instanceof ProcedureSetting) {
            return $existing;
        }

        return $this->procedureSettingRepository->createProcedureSetting([
            'company_id' => $project->company_id,
            'name' => 'Project Procedures',
            'type' => self::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => null,
        ]);
    }

    private function findProjectProcedureParentOrFail(
        ProjectManagement $project,
        string $parentProcedureSettingId
    ): ProcedureSetting {
        return ProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('id', $parentProcedureSettingId)
            ->where('company_id', $project->company_id)
            ->where('type', self::PROCEDURE_TYPE)
            ->whereNull('parent_id')
            ->whereHas('workFlow', static function ($query) use ($project): void {
                $query->where('company_id', $project->company_id)
                    ->where('project_id', $project->id)
                    ->where('type', self::PROCEDURE_TYPE);
            })
            ->firstOrFail();
    }

    private function projectWorkFlowName(ProjectManagement $project): string
    {
        return 'project_'.$project->id;
    }

    private function assertReceiverCompanyIsSharedWithProject(ProjectManagement $project, string $receiverCompanyId): void
    {
        $isAcceptedSharedCompany = ResourceShare::query()
            ->where('shareable_type', ProjectManagement::class)
            ->where('shareable_id', $project->id)
            ->where('owner_company_id', $project->company_id)
            ->where('status', 'accepted')
            ->where('shared_with_company_id', $receiverCompanyId)
            ->exists();

        if (! $isAcceptedSharedCompany) {
            throw ValidationException::withMessages([
                'receiver_company_id' => 'Selected receiver company must be an accepted shared company for this project.',
            ]);
        }
    }

    private function procedureSettingPayload(ProjectManagement $project, array $data): array
    {
        $payload = [
            'company_id' => $project->company_id,
            'name' => $data['name'],
            'type' => self::PROCEDURE_TYPE,
            'execute_type' => $data['execute_type'] ?? 'sequence',
            'is_active' => $data['is_active'] ?? true,
        ];

        foreach (['icon', 'percentage', 'deadline_days', 'deadline_hours', 'escalation_management_hierarchy_id', 'sort_order'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        return $payload;
    }
}
