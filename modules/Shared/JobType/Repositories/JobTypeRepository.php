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

    public function withoutScopePaginated(array $conditions=[], $page=1, $perPage=10)
    {
         $query = $this->model->withoutGlobalScope("active")->where($conditions);
        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        return array_merge($paginationArray,[
            'data' => $paginatedData
        ]);
    }

    public function getJobTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAllJobTypes(): Collection
    {
        return $this->model->all();
    }

    public function getJobType(UuidInterface $id): JobType
    {
        return $this->model->withoutGlobalScope("active")->where('id', $id)->first();
    }

    public function createJobType(array $data): JobType
    {
        return $this->create($data);
    }

    public function updateJobType(UuidInterface $id, array $data): bool
    {
        return $this->model->withoutGlobalScope("active")->where('id', $id)->first()->update($data);
    }

    public function deleteJobType(UuidInterface $id): bool
    {
        return $this->model->withoutGlobalScope("active")->where('id', $id)->first()->delete($id);
    }
}
