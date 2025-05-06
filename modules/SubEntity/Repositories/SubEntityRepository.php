<?php

declare(strict_types=1);

namespace Modules\SubEntity\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\SubEntity\Models\SubEntity;

/**
 * @property SubEntity $model
 * @method SubEntity findOneOrFail($id)
 * @method SubEntity findOneByOrFail(array $data)
 */
class SubEntityRepository extends BaseRepository
{
    public function __construct(SubEntity $model)
    {
        parent::__construct($model);
    }

    public function getSubEntityList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSubEntity(UuidInterface $id): SubEntity
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createSubEntity(array $data): SubEntity
    {
        return $this->create($data);
    }

    public function updateSubEntity(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteSubEntity(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getPaginatedByProgramName(string $programName, int $page = 1, int $perPage = 15): array
    {
        $query = $this->model->newQuery()
            ->whereHas('mainProgram', function ($q) use ($programName): void {
                $q->where('name', $programName);
            });

        $count = $query->count();
        $data = $query->forPage($page, $perPage)->orderBy('created_at', 'desc')->get();
        $pagination = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'data' => $data,
            'pagination' => $pagination['pagination'],
        ];
    }
}
