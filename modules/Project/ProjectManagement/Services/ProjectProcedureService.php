<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    public function list(string $projectId): Collection
    {
        $project = $this->findOwnedProjectOrFail($projectId);

        return $this->repository->listForProject($project->id, self::PROCEDURE_TYPE);
    }

    public function get(string $projectId, string $procedureSettingId): ProjectProcedureSetting
    {
        $project = $this->findOwnedProjectOrFail($projectId);

        return $this->repository->findForProject($project->id, $procedureSettingId, self::PROCEDURE_TYPE);
    }

    public function create(string $projectId, array $procedureData, array $metadata): ProjectProcedureSetting
    {
        $project = $this->findOwnedProjectOrFail($projectId);
        $receiverCompanyId = $metadata['receiver_company_id'] ?? null;

        if ($receiverCompanyId) {
            $this->assertReceiverCompanyIsSharedWithProject($project, (string) $receiverCompanyId);
        }

        return DB::transaction(function () use ($project, $procedureData, $metadata): ProjectProcedureSetting {
            $procedureSetting = $this->procedureSettingRepository->createProcedureSetting(
                $this->procedureSettingPayload($project, $procedureData)
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
        array $metadata
    ): ProjectProcedureSetting {
        $project = $this->findOwnedProjectOrFail($projectId);
        $projectProcedure = $this->repository->findForProject($project->id, $procedureSettingId, self::PROCEDURE_TYPE);
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

    public function delete(string $projectId, string $procedureSettingId): void
    {
        $project = $this->findOwnedProjectOrFail($projectId);
        $projectProcedure = $this->repository->findForProject($project->id, $procedureSettingId, self::PROCEDURE_TYPE);

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
