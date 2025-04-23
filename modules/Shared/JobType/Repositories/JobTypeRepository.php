<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\JobType\Models\JobType;

/**
 * @property JobType $model
 * @method JobType findOneOrFail($id)
 * @method JobType findOneByOrFail(array $data)
 */
class JobTypeRepository extends BaseRepository
{
    public function __construct(JobType $model)
    {
        parent::__construct($model);
    }

    public function getJobTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getJobType(UuidInterface $id): JobType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createJobType(array $data): JobType
    {
        return $this->create($data);
    }

    public function updateJobType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteJobType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
