<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\InternalProcessType\DTO\CreateInternalProcessTypeDTO;
use Modules\Shared\InternalProcessType\DTO\UpdateInternalProcessTypeDTO;
use Modules\Shared\InternalProcessType\Models\InternalProcessType;
use Modules\Shared\InternalProcessType\Repositories\InternalProcessTypeRepository;
use Modules\Shared\InternalProcessType\Support\InternalProcessTypePayload;

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
        return $this->repository->createType($dto->toArray());
    }

    public function update(UpdateInternalProcessTypeDTO $dto): InternalProcessType
    {
        $existing = $this->repository->findByIdOrFail($dto->id);
        $data = array_filter([
            'name'       => $dto->name,
            'is_active'  => $dto->isActive,
            'sort_order' => $dto->sortOrder,
        ], static fn ($v) => $v !== null);

        if ($dto->form !== null || $dto->conditions !== null || $dto->ordering !== null) {
            $data['settings'] = InternalProcessTypePayload::mergeStored(
                $existing->settings,
                $dto->form,
                $dto->conditions,
                $dto->ordering,
            );
        }

        return $this->repository->updateType($dto->id, $data);
    }

    public function delete(string $id): void
    {
        $this->repository->deleteType($id);
    }
}
