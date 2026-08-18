<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\Project\ProjectManagement\DTO\CreateProjectManagementDTO;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectTag;
use Modules\Project\ProjectManagement\Repositories\ProjectManagementRepository;
use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class ProjectManagementCRUDService
{
    use HasExportService;

    public function __construct(
        private ProjectManagementRepository $repository,
    ) {
    }

    public function create(CreateProjectManagementDTO $createProjectManagementDTO): ProjectManagement
    {
         return $this->repository->createProjectManagement($createProjectManagementDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10, ?User $user = null, array $filters = []): array
    {
        return $this->repository->paginatedForUser(
            page: $page,
            perPage: $perPage,
            user: $user,
            filters: $filters,
        );
    }

    public function get(UuidInterface $id): ProjectManagement
    {
        return $this->repository->getProjectManagement(
            id: $id,
        );
    }

    public function uploadStamp(UuidInterface $id, UploadedFile $stamp): ProjectManagement
    {
        $project = $this->repository->getProjectManagement($id);

        $project->clearMediaCollection(ProjectManagement::STAMP_COLLECTION);
        $project->addMedia($stamp)->toMediaCollection(ProjectManagement::STAMP_COLLECTION);

        return $project->fresh() ?? $project;
    }

    public function projectTags(): array
    {
        return ProjectTag::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'sort_order'])
            ->map(fn (ProjectTag $tag) => [
                'id' => $tag->id,
                'name' => $tag->getTranslation('name'),
                'code' => $tag->code,
                'sort_order' => $tag->sort_order,
            ])->toArray();
    }
}
