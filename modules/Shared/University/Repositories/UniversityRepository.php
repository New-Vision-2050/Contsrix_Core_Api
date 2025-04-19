<?php

declare(strict_types=1);

namespace Modules\Shared\University\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\University\Models\University;
use Ramsey\Uuid\UuidInterface;
/**
 * @property University $model
 * @method University findOneOrFail($id)
 * @method University findOneByOrFail(array $data)
 */
class UniversityRepository extends BaseRepository
{
    public function __construct(University $model)
    {
        parent::__construct($model);
    }

    public function getUniversityList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUniversity(UuidInterface $id): University
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUniversity(array $data): University
    {
        return $this->create($data);
    }

    public function updateUniversity(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUniversity(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
