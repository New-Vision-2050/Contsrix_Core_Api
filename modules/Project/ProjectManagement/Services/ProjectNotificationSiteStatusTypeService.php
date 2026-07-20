<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationSiteStatusTypeDTO;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationSiteStatusTypeKeyDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationSiteStatusTypeDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationSiteStatusTypeKeyDTO;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusType;
use Modules\Project\ProjectManagement\Repositories\ProjectNotificationSiteStatusTypeRepository;

class ProjectNotificationSiteStatusTypeService
{
    public function __construct(
        private readonly ProjectNotificationSiteStatusTypeRepository $repository,
        private readonly ProjectNotificationSiteStatusTypeKeyService $keyService,
    ) {}

    public function list(?int $projectTypeId = null, ?string $notificationTypeId = null): Collection
    {
        return $this->repository->listActive($projectTypeId, $notificationTypeId);
    }

    public function listWithKeys(?int $projectTypeId = null, ?string $notificationTypeId = null): Collection
    {
        return $this->repository->listWithActiveKeys($projectTypeId, $notificationTypeId);
    }

    /**
     * Resolve the project_type_id to filter by. Accepts an explicit
     * project_type_id, or a project_id whose project_type_id is looked up.
     * project_type_id takes precedence if both are provided.
     */
    public function resolveProjectTypeId(?int $projectTypeId, ?string $projectId): ?int
    {
        if ($projectTypeId) {
            return $projectTypeId;
        }

        if ($projectId) {
            $project = ProjectManagement::withoutGlobalScopes()->find($projectId);

            return $project?->project_type_id;
        }

        return null;
    }

    public function show(string $id): ProjectNotificationSiteStatusType
    {
        return $this->repository->findOneOrFail($id);
    }

    public function create(CreateProjectNotificationSiteStatusTypeDTO $dto): ProjectNotificationSiteStatusType
    {
        return DB::transaction(function () use ($dto) {
            $type = $this->repository->create($dto->toArray());

            if (! empty($dto->notificationTypes)) {
                $type->notificationTypes()->sync($dto->notificationTypes);
            }

            foreach ($dto->keys ?? [] as $keyData) {
                $this->keyService->create($this->makeKeyDTO($type->id, $keyData));
            }

            return $type->fresh(['activeKeys', 'notificationTypes']);
        });
    }

    public function update(string $id, UpdateProjectNotificationSiteStatusTypeDTO $dto): ProjectNotificationSiteStatusType
    {
        return DB::transaction(function () use ($id, $dto) {
            $type = $this->repository->findOneOrFail($id);
            $type->update($dto->toArray());

            if ($dto->notificationTypes !== null) {
                $type->notificationTypes()->sync($dto->notificationTypes);
            }

            if ($dto->keys !== null) {
                $this->syncKeys($type, $dto->keys);
            }

            return $type->fresh(['activeKeys', 'notificationTypes']);
        });
    }

    public function delete(string $id): bool
    {
        $type = $this->repository->findOneOrFail($id);

        return $type->delete();
    }

    /**
     * @param array<string, mixed> $keyData
     */
    private function makeKeyDTO(string $typeId, array $keyData): CreateProjectNotificationSiteStatusTypeKeyDTO
    {
        return new CreateProjectNotificationSiteStatusTypeKeyDTO(
            siteStatusTypeId: $typeId,
            nameAr: $keyData['name_ar'],
            nameEn: $keyData['name_en'] ?? null,
            key: $keyData['key'] ?? '',
            fieldType: $keyData['field_type'] ?? 'text',
            options: $keyData['options'] ?? null,
            showInSiteStatusUpdates: (bool) ($keyData['show_in_site_status_updates'] ?? false),
            sortOrder: (int) ($keyData['sort_order'] ?? 0),
            isActive: (bool) ($keyData['is_active'] ?? true),
        );
    }

    /**
     * Sync keys: update existing (by id), create new (without id), delete missing.
     *
     * @param array<int, array<string, mixed>> $keysData
     */
    private function syncKeys(ProjectNotificationSiteStatusType $type, array $keysData): void
    {
        $existingIds = $type->keys()->pluck('id')->all();
        $keepIds = [];

        foreach ($keysData as $keyData) {
            $id = $keyData['id'] ?? null;

            if ($id && in_array($id, $existingIds, true)) {
                $this->keyService->update($id, $this->makeUpdateKeyDTO($keyData));
                $keepIds[] = $id;
            } else {
                $this->keyService->create($this->makeKeyDTO($type->id, $keyData));
            }
        }

        $deleteIds = array_diff($existingIds, $keepIds);
        foreach ($deleteIds as $deleteId) {
            $this->keyService->delete($deleteId);
        }
    }

    /**
     * @param array<string, mixed> $keyData
     */
    private function makeUpdateKeyDTO(array $keyData): UpdateProjectNotificationSiteStatusTypeKeyDTO
    {
        return new UpdateProjectNotificationSiteStatusTypeKeyDTO(
            nameAr: $keyData['name_ar'] ?? null,
            nameEn: $keyData['name_en'] ?? null,
            key: $keyData['key'] ?? null,
            fieldType: $keyData['field_type'] ?? null,
            options: array_key_exists('options', $keyData) ? $keyData['options'] : null,
            showInSiteStatusUpdates: array_key_exists('show_in_site_status_updates', $keyData)
                ? (bool) $keyData['show_in_site_status_updates']
                : null,
            sortOrder: array_key_exists('sort_order', $keyData) ? (int) $keyData['sort_order'] : null,
            isActive: array_key_exists('is_active', $keyData) ? (bool) $keyData['is_active'] : null,
        );
    }
}
