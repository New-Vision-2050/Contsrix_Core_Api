<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\ProcedureSetting\Repositories\ProcedureSettingRepository;
use Modules\ProcedureSetting\Services\ProcedureSettingCloneService;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureJobAttribute;
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
        private readonly ProcedureSettingCloneService $cloneService,
    ) {}

    public function list(string $projectId, ?string $parentProcedureSettingId = null): Collection
    {
        $project = $this->findOwnedProjectOrFail($projectId);

        if ($parentProcedureSettingId !== null) {
            $this->findProjectProcedureParentOrFail($project, $parentProcedureSettingId);
        } else {
            $workFlow = $this->projectWorkFlow($project);
            $parent = $this->projectProcedureParent($project, $workFlow);
            $parentProcedureSettingId = $parent->id;
        }

        return $this->repository->listForProject(
            $project->id,
            self::PROCEDURE_TYPE,
            $parentProcedureSettingId,
            $project->company_id,
            $this->readerCompanyId(),
        );
    }

    /**
     * @return array<int, array{id: string, name: string, code: string, is_active: bool}>
     */
    public function listJobAttributes(): array
    {
        return ProjectProcedureJobAttribute::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active'])
            ->map(static fn (ProjectProcedureJobAttribute $jobAttribute): array => [
                'id' => $jobAttribute->id,
                'name' => $jobAttribute->name,
                'code' => $jobAttribute->code,
                'is_active' => (bool) $jobAttribute->is_active,
            ])
            ->values()
            ->all();
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
            $parentProcedureSettingId,
            $project->company_id,
            $this->readerCompanyId(),
        );
    }

    public function create(
        string $projectId,
        array $procedureData,
        array $metadata,
        ?string $parentProcedureSettingId = null,
        ?array $receiverCompanyIds = null,
        ?string $sourceProcedureSettingId = null,
    ): ProjectProcedureSetting
    {
        $project = $this->findOwnedProjectOrFail($projectId);

        return DB::transaction(function () use (
            $project,
            $procedureData,
            $metadata,
            $parentProcedureSettingId,
            $receiverCompanyIds,
            $sourceProcedureSettingId,
        ): ProjectProcedureSetting {
            if ($parentProcedureSettingId !== null) {
                $parent = $this->findProjectProcedureParentOrFail($project, $parentProcedureSettingId);
                $workFlowId = $parent->work_flow_id;
            } else {
                $workFlow = $this->projectWorkFlow($project);
                $parent = $this->projectProcedureParent($project, $workFlow);
                $workFlowId = $workFlow->id;
            }

            $source = null;
            if ($sourceProcedureSettingId !== null) {
                $source = $this->findCloneSourceOrFail($project, $sourceProcedureSettingId, (string) $parent->id);
                $procedureData = array_replace($this->sourceProcedureData($source), $procedureData);
                $metadata = array_replace($this->sourceMetadata($source), $metadata);

                if ($receiverCompanyIds === null) {
                    $receiverCompanyIds = $this->sourceReceiverCompanyIds($source);
                }
            }

            $this->assertReceiverCompaniesAreSharedWithProject($project, $receiverCompanyIds ?? [], 'receiver_company_ids');

            $procedureSetting = $this->procedureSettingRepository->createProcedureSetting(
                array_merge($this->procedureSettingPayload($project, $procedureData), [
                    'work_flow_id' => $workFlowId,
                    'parent_id' => $parent->id,
                ])
            );

            $projectProcedure = $this->repository->createProjectProcedure(array_merge($metadata, [
                'company_id' => $project->company_id,
                'project_id' => $project->id,
                'procedure_setting_id' => $procedureSetting->id,
            ]));

            if ($receiverCompanyIds !== null) {
                $projectProcedure->receiverCompanies()->sync($receiverCompanyIds);
            }

            if ($source instanceof ProjectProcedureSetting) {
                $sourceProcedureSetting = $source->procedureSetting;

                if ($sourceProcedureSetting instanceof ProcedureSetting) {
                    $this->cloneService->duplicateSteps((string) $source->procedure_setting_id, (string) $procedureSetting->id);
                    $this->duplicateChildProcedureSettings($project, $sourceProcedureSetting, $procedureSetting);
                }
            }

            return $this->repository->loadRelations($projectProcedure->refresh());
        });
    }

    public function update(
        string $projectId,
        string $procedureSettingId,
        array $procedureData,
        array $metadata,
        ?string $parentProcedureSettingId = null,
        ?array $receiverCompanyIds = null,
    ): ProjectProcedureSetting {
        $project = $this->findOwnedProjectOrFail($projectId);
        $this->assertReceiverCompaniesAreSharedWithProject($project, $receiverCompanyIds ?? [], 'receiver_company_ids');
        $projectProcedure = $this->repository->findForProject(
            $project->id,
            $procedureSettingId,
            self::PROCEDURE_TYPE,
            $parentProcedureSettingId,
            $project->company_id,
            $this->readerCompanyId(),
        );

        return DB::transaction(function () use ($projectProcedure, $procedureData, $metadata, $receiverCompanyIds): ProjectProcedureSetting {
            if ($procedureData !== []) {
                $this->procedureSettingRepository->updateProcedureSetting(
                    Uuid::fromString($projectProcedure->procedure_setting_id),
                    $procedureData
                );
            }

            if ($metadata !== []) {
                $projectProcedure = $this->repository->updateProjectProcedure($projectProcedure, $metadata);
            }

            if ($receiverCompanyIds !== null) {
                $projectProcedure->receiverCompanies()->sync($receiverCompanyIds);
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
            $parentProcedureSettingId,
            $project->company_id,
            $this->readerCompanyId(),
        );

        DB::transaction(function () use ($projectProcedure): void {
            $this->procedureSettingRepository->deleteProcedureSetting(
                Uuid::fromString($projectProcedure->procedure_setting_id)
            );
        });
    }

    private function findOwnedProjectOrFail(string $projectId): ProjectManagement
    {
        // No tenancy restriction: projects can be shared across tenants, so any
        // authenticated user with the correct permission may access it here.
        return ProjectManagement::withoutGlobalScopes()
            ->where('id', $projectId)
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
            ->whereHas('workFlow', static function ($query) use ($project): void {
                $query->withoutGlobalScopes()
                    ->where('company_id', $project->company_id)
                    ->where('project_id', $project->id)
                    ->where('type', self::PROCEDURE_TYPE);
            })
            ->firstOrFail();
    }

    private function projectWorkFlowName(ProjectManagement $project): string
    {
        return 'project_'.$project->id;
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

    private function readerCompanyId(): string
    {
        $headerTenantId = request()->header('X-Tenant');
        if (is_string($headerTenantId) && $headerTenantId !== '') {
            return $headerTenantId;
        }

        $tenantId = tenant('id');
        if ($tenantId !== null) {
            return (string) $tenantId;
        }

        return '';
    }

    private function assertReceiverCompaniesAreSharedWithProject(
        ProjectManagement $project,
        array $receiverCompanyIds,
        string $field
    ): void {
        $receiverCompanyIds = collect($receiverCompanyIds)
            ->map(static fn (mixed $id): string => (string) $id)
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($receiverCompanyIds === []) {
            return;
        }

        $acceptedCompanyIds = ResourceShare::query()
            ->where('shareable_type', ProjectManagement::class)
            ->where('shareable_id', $project->id)
            ->where('owner_company_id', $project->company_id)
            ->where('status', 'accepted')
            ->whereIn('shared_with_company_id', $receiverCompanyIds)
            ->pluck('shared_with_company_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        $acceptedCompanyIds[] = (string) $project->company_id;

        if (count(array_diff($receiverCompanyIds, $acceptedCompanyIds)) === 0) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'Selected receiver companies must be accepted shared companies for this project.',
        ]);
    }

    private function findCloneSourceOrFail(
        ProjectManagement $project,
        string $sourceProcedureSettingId,
        string $parentProcedureSettingId
    ): ProjectProcedureSetting {
        return $this->repository->findForProject(
            $project->id,
            $sourceProcedureSettingId,
            self::PROCEDURE_TYPE,
            $parentProcedureSettingId,
            $project->company_id,
            $project->company_id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceProcedureData(ProjectProcedureSetting $source): array
    {
        $procedureSetting = $source->procedureSetting;

        if (! $procedureSetting instanceof ProcedureSetting) {
            return [];
        }

        $data = [];
        foreach ([
            'name',
            'execute_type',
            'icon',
            'percentage',
            'deadline_days',
            'deadline_hours',
            'escalation_management_hierarchy_id',
            'sort_order',
            'is_active',
        ] as $key) {
            $data[$key] = $procedureSetting->getAttribute($key);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceMetadata(ProjectProcedureSetting $source): array
    {
        $data = [];
        foreach ([
            'attachment_type_id',
            'attachment_sub_type_id',
            'attachment_sub_sub_type_id',
            'job_attribute_id',
            'used_in_document_cycle',
            'appears_in_archive_after_approval',
            'appears_in_attachments_library',
            'requires_asset_id',
        ] as $key) {
            $data[$key] = $source->getAttribute($key);
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function sourceReceiverCompanyIds(ProjectProcedureSetting $source): array
    {
        if (! $source->relationLoaded('receiverCompanies')) {
            $source->load('receiverCompanies');
        }

        return $source->receiverCompanies
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }

    private function duplicateChildProcedureSettings(
        ProjectManagement $project,
        ProcedureSetting $sourceParent,
        ProcedureSetting $targetParent
    ): void {
        $sourceChildren = ProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $project->company_id)
            ->where('type', self::PROCEDURE_TYPE)
            ->where('work_flow_id', $sourceParent->work_flow_id)
            ->where('parent_id', $sourceParent->id)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($sourceChildren as $sourceChild) {
            $sourceProjectProcedure = $this->projectProcedureForSettingOrFail($project, (string) $sourceChild->id);
            $receiverCompanyIds = $this->sourceReceiverCompanyIds($sourceProjectProcedure);

            $this->assertReceiverCompaniesAreSharedWithProject(
                $project,
                $receiverCompanyIds,
                'source_procedure_setting_id',
            );

            $targetChild = $this->procedureSettingRepository->createProcedureSetting(array_merge(
                $this->procedureSettingPayload($project, $this->sourceProcedureData($sourceProjectProcedure)),
                [
                    'work_flow_id' => $targetParent->work_flow_id,
                    'parent_id' => $targetParent->id,
                ],
            ));

            $targetProjectProcedure = $this->repository->createProjectProcedure(array_merge(
                $this->sourceMetadata($sourceProjectProcedure),
                [
                    'company_id' => $project->company_id,
                    'project_id' => $project->id,
                    'procedure_setting_id' => $targetChild->id,
                ],
            ));

            $targetProjectProcedure->receiverCompanies()->sync($receiverCompanyIds);

            $this->cloneService->duplicateSteps((string) $sourceChild->id, (string) $targetChild->id);
            $this->duplicateChildProcedureSettings($project, $sourceChild, $targetChild);
        }
    }

    private function projectProcedureForSettingOrFail(
        ProjectManagement $project,
        string $procedureSettingId
    ): ProjectProcedureSetting {
        return ProjectProcedureSetting::query()
            ->withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->where('company_id', $project->company_id)
            ->where('procedure_setting_id', $procedureSettingId)
            ->with([
                'procedureSetting',
                'receiverCompanies',
            ])
            ->firstOrFail();
    }

}
