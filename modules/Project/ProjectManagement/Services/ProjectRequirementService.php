<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Project\ProjectManagement\Repositories\ProjectRequirementRepository;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Modules\Shared\ResourceShare\Models\ResourceShare;

class ProjectRequirementService
{
    public function __construct(private readonly ProjectRequirementRepository $repository) {}

    public function list(string $projectId, array $filters, int $page, int $perPage): array
    {
        $project = $this->findReadableProjectOrFail($projectId);
        $readerCompanyId = (string) tenant('id');
        $isOwner = $project->company_id === $readerCompanyId;
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);

        $paginator = $this->repository->paginateForProject(
            $project->id,
            $filters,
            $page,
            $perPage,
            $readerCompanyId,
            $isOwner,
        );

        return [
            'data' => $paginator->getCollection(),
            'summary' => $this->repository->summaryForProject($project->id, $filters, $readerCompanyId, $isOwner),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'result_count' => $paginator->total(),
            ],
        ];
    }

    public function get(string $projectId, string $requirementId): ProjectRequirement
    {
        $project = $this->findReadableProjectOrFail($projectId);
        $readerCompanyId = (string) tenant('id');

        return $this->repository->findForProject(
            $project->id,
            $requirementId,
            $readerCompanyId,
            $project->company_id === $readerCompanyId,
        );
    }

    public function createMany(string $projectId, array $rows): Collection
    {
        $project = $this->findOwnedProjectOrFail($projectId);

        return DB::transaction(function () use ($project, $rows): Collection {
            $created = new Collection;

            foreach ($rows as $index => $row) {
                $receiverCompanyIds = $this->receiverCompanyIdsFrom($row);
                $this->assertReceiverCompaniesAreSharedWithProject(
                    $project,
                    $receiverCompanyIds,
                    "requirements.{$index}.receiver_company_ids",
                );

                $requirement = $this->repository->createForProject(
                    $this->payloadForProject($project, Arr::except($row, ['receiver_company_ids']))
                );
                $requirement->receiverCompanies()->sync($receiverCompanyIds);

                $created->push($this->repository->loadRelations($requirement->refresh()));
            }

            return $created;
        });
    }

    public function update(string $projectId, string $requirementId, array $data): ProjectRequirement
    {
        $project = $this->findOwnedProjectOrFail($projectId);
        $requirement = $this->repository->findForProject($project->id, $requirementId);

        return DB::transaction(function () use ($project, $requirement, $data): ProjectRequirement {
            $syncReceiverCompanies = array_key_exists('receiver_company_ids', $data);
            $receiverCompanyIds = $this->receiverCompanyIdsFrom($data);

            if ($syncReceiverCompanies) {
                $this->assertReceiverCompaniesAreSharedWithProject(
                    $project,
                    $receiverCompanyIds,
                    'receiver_company_ids',
                );
            }

            $requirement = $this->repository->updateRequirement(
                $requirement,
                $this->payloadForProject($project, Arr::except($data, ['receiver_company_ids']), $requirement)
            );

            if ($syncReceiverCompanies) {
                $requirement->receiverCompanies()->sync($receiverCompanyIds);
            }

            return $this->repository->loadRelations($requirement->refresh());
        });
    }

    public function delete(string $projectId, string $requirementId): void
    {
        $project = $this->findOwnedProjectOrFail($projectId);
        $requirement = $this->repository->findForProject($project->id, $requirementId);

        $this->repository->deleteRequirement($requirement);
    }

    private function findOwnedProjectOrFail(string $projectId): ProjectManagement
    {
        return ProjectManagement::withoutGlobalScopes()
            ->where('id', $projectId)
            ->where('company_id', tenant('id'))
            ->firstOrFail();
    }

    private function findReadableProjectOrFail(string $projectId): ProjectManagement
    {
        return ProjectManagement::query()
            ->where('id', $projectId)
            ->firstOrFail();
    }

    private function receiverCompanyIdsFrom(array $data): array
    {
        if (! array_key_exists('receiver_company_ids', $data)) {
            return [];
        }

        return collect($data['receiver_company_ids'])
            ->map(static fn (mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function assertReceiverCompaniesAreSharedWithProject(
        ProjectManagement $project,
        array $receiverCompanyIds,
        string $field
    ): void {
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

        if (count(array_diff($receiverCompanyIds, $acceptedCompanyIds)) === 0) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'Selected receiver companies must be accepted shared companies for this project.',
        ]);
    }

    private function payloadForProject(
        ProjectManagement $project,
        array $data,
        ?ProjectRequirement $existing = null
    ): array {
        $payload = $existing === null ? [
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'evaluation_status' => ProjectRequirementEvaluationStatus::default(),
            'completion_percentage' => 0,
        ] : [];

        foreach ($data as $key => $value) {
            $payload[$key] = is_string($value) ? trim($value) : $value;
        }

        $this->hydrateLookupNames($payload);

        if (array_key_exists('repetition', $payload)) {
            $payload['repetition_interval_type'] = $payload['repetition_interval_type']
                ?? ProjectRequirementRepetition::intervalTypeFor((string) $payload['repetition']);

            if (! in_array($payload['repetition'], [
                ProjectRequirementRepetition::Daily->value,
                ProjectRequirementRepetition::Weekly->value,
            ], true) && ! array_key_exists('repeat_days', $data)) {
                $payload['repeat_days'] = null;
            }
        }

        return $payload;
    }

    private function hydrateLookupNames(array &$payload): void
    {
        if (array_key_exists('specialization_id', $payload) && $payload['specialization_id'] !== null) {
            $specialization = AcademicSpecialization::query()
                ->withoutGlobalScopes()
                ->find($payload['specialization_id']);

            if ($specialization instanceof AcademicSpecialization && empty($payload['specialization'])) {
                $payload['specialization'] = (string) $specialization->name;
            }
        }

        foreach ([
            'sending_entity_id' => 'sending_entity',
            'review_entity_id' => 'review_entity',
        ] as $idKey => $nameKey) {
            if (! array_key_exists($idKey, $payload) || $payload[$idKey] === null || ! empty($payload[$nameKey])) {
                continue;
            }

            $company = Company::query()
                ->withoutGlobalScopes()
                ->find($payload[$idKey]);

            if ($company instanceof Company) {
                $payload[$nameKey] = is_array($company->name)
                    ? ($company->name['ar'] ?? $company->name['en'] ?? reset($company->name))
                    : $company->name;
            }
        }
    }
}
