<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\InternalProcessType\Models\InternalProcessType;

/** @property InternalProcessType $model */
class InternalProcessTypeRepository extends BaseRepository
{
    public function __construct(InternalProcessType $model)
    {
        parent::__construct($model);
    }

    public function paginateByEntityType(?string $entityType, int $page, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->orderBy('sort_order')->orderBy('name');

        if ($entityType !== null && $entityType !== '') {
            $query->where('entity_type', $entityType);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function listActiveByEntityType(string $entityType): Collection
    {
        return $this->model->newQuery()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findById(string $id): ?InternalProcessType
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdOrFail(string $id): InternalProcessType
    {
        return $this->findOneByOrFail(['id' => $id]);
    }

    public function createType(array $data): InternalProcessType
    {
        return $this->create($data);
    }

    public function updateType(string $id, array $data): InternalProcessType
    {
        $this->update($id, $data);

        return $this->findByIdOrFail($id);
    }

    public function deleteType(string $id): bool
    {
        return $this->delete($id);
    }
}
