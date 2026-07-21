<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;

/**
 * @property ProjectRequirement $model
 */
class ProjectRequirementRepository extends BaseRepository
{
    public function __construct(ProjectRequirement $model)
    {
        parent::__construct($model);
    }

    public function paginateForProject(
        string $projectId,
        array $filters,
        int $page,
        int $perPage,
        ?string $readerCompanyId = null,
        bool $isOwner = true
    ): LengthAwarePaginator {
        return $this->queryForProject($projectId, $filters, $readerCompanyId, $isOwner)
            ->orderByDesc('created_at')
            ->orderBy('requirement_code')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function findForProject(
        string $projectId,
        string $id,
        ?string $readerCompanyId = null,
        bool $isOwner = true
    ): ProjectRequirement {
        return $this->queryForProject($projectId, [], $readerCompanyId, $isOwner)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function createForProject(array $data): ProjectRequirement
    {
        /** @var ProjectRequirement $requirement */
        $requirement = $this->create($data);

        return $this->loadRelations($requirement);
    }

    public function updateRequirement(ProjectRequirement $requirement, array $data): ProjectRequirement
    {
        $requirement->fill($data);
        $requirement->save();

        return $this->loadRelations($requirement->refresh());
    }

    public function deleteRequirement(ProjectRequirement $requirement): bool
    {
        return (bool) $requirement->delete();
    }

    public function summaryForProject(
        string $projectId,
        array $filters = [],
        ?string $readerCompanyId = null,
        bool $isOwner = true
    ): array {
        $baseQuery = $this->queryForProject($projectId, $filters, $readerCompanyId, $isOwner);
        $summary = [
            'total' => (clone $baseQuery)->count(),
        ];

        foreach (ProjectRequirementEvaluationStatus::values() as $status) {
            $summary[$status] = (clone $baseQuery)
                ->where('evaluation_status', $status)
                ->count();
        }

        return $summary;
    }

    public function loadRelations(ProjectRequirement $requirement): ProjectRequirement
    {
        return $requirement->load($this->relations());
    }

    private function queryForProject(
        string $projectId,
        array $filters = [],
        ?string $readerCompanyId = null,
        bool $isOwner = true
    ): Builder {
        $readerCompanyId ??= (string) tenant('id');

        $query = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->with($this->relations());

        if ($isOwner) {
            $query->where('company_id', $readerCompanyId);
        } else {
            $query->whereHas('receiverCompanies', static function (Builder $query) use ($readerCompanyId): void {
                $query->where('companies.id', $readerCompanyId);
            });
        }

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyReceiverCompanyFilter($query, $filters['receiver_company_id'] ?? null);
        $this->applyExactFilters($query, $filters);

        return $query;
    }

    private function applySearch(Builder $query, mixed $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $search = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $search).'%';

        $query->where(static function (Builder $query) use ($search): void {
            foreach ([
                'requirement_code',
                'required_document_name',
                'document',
                'document_type',
                'specialization',
                'stage',
                'sending_entity',
                'review_entity',
                'resulting_document',
            ] as $column) {
                $query->orWhere($column, 'like', $search);
            }
        });
    }

    private function applyExactFilters(Builder $query, array $filters): void
    {
        foreach ([
            'document_type_id',
            'document_type',
            'specialization_id',
            'specialization',
            'stage',
            'sending_entity_id',
            'sending_entity',
            'review_entity_id',
            'review_entity',
            'evaluation_status',
            'repetition',
        ] as $key) {
            if (! array_key_exists($key, $filters) || $filters[$key] === null || $filters[$key] === '') {
                continue;
            }

            $query->where($key, $filters[$key]);
        }
    }

    private function applyReceiverCompanyFilter(Builder $query, mixed $receiverCompanyId): void
    {
        if ($receiverCompanyId === null || $receiverCompanyId === '') {
            return;
        }

        $query->whereHas('receiverCompanies', static function (Builder $query) use ($receiverCompanyId): void {
            $query->where('companies.id', (string) $receiverCompanyId);
        });
    }

    private function relations(): array
    {
        return [
            'project:id,name,company_id,serial_number',
            'documentType:id,name,company_id,is_active',
            'specializationLookup:id,code,academic_qualification_id',
            'sendingEntityCompany:id,name,serial_no,email,phone',
            'reviewEntityCompany:id,name,serial_no,email,phone',
            'receiverCompanies',
        ];
    }
}
