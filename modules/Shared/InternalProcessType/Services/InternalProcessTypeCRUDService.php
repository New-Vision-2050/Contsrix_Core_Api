<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\InternalProcessType\DTO\CreateInternalProcessTypeDTO;
use Modules\Shared\InternalProcessType\DTO\UpdateInternalProcessTypeDTO;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Models\InternalProcessType;
use Modules\Shared\InternalProcessType\Repositories\InternalProcessTypeRepository;

final class InternalProcessTypeCRUDService
{
    public function __construct(
        private readonly InternalProcessTypeRepository $repository,
    ) {}

    public function list(?string $entityType, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateByEntityType($entityType, $page, $perPage);
    }

    public function listActive(string $entityType): Collection
    {
        return $this->repository->listActiveByEntityType($entityType);
    }

    public function get(string $id): InternalProcessType
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function create(CreateInternalProcessTypeDTO $dto): InternalProcessType
    {
        $data = $dto->toArray();
        $data['settings'] = $this->normalizeSettings($data['settings'] ?? []);

        return $this->repository->createType($data);
    }

    public function update(UpdateInternalProcessTypeDTO $dto): InternalProcessType
    {
        $data = $dto->toArray();

        if (array_key_exists('settings', $data) && is_array($data['settings'])) {
            $existing = $this->repository->findByIdOrFail($dto->id);
            $data['settings'] = $this->normalizeSettings(
                array_merge($existing->settings ?? [], $data['settings'])
            );
        }

        return $this->repository->updateType($dto->id, $data);
    }

    public function delete(string $id): void
    {
        $this->repository->deleteType($id);
    }

    private function normalizeSettings(array $settings): array
    {
        $defaults = InternalProcessCondition::defaultSettings();

        foreach (InternalProcessCondition::cases() as $condition) {
            $key = $condition->value;
            if (! array_key_exists($key, $settings)) {
                continue;
            }

            if ($condition->valueType() === 'integer') {
                $defaults[$key] = $settings[$key] !== null ? (int) $settings[$key] : null;
            } else {
                $defaults[$key] = (bool) $settings[$key];
            }
        }

        return $defaults;
    }
}
