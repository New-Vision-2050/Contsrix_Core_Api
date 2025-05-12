<?php

declare(strict_types=1);

namespace Modules\JobTitle\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\JobTitle\Models\JobTitle;

/**
 * @property JobTitle $model
 * @method JobTitle findOneOrFail($id)
 * @method JobTitle findOneByOrFail(array $data)
 */
class JobTitleRepository extends BaseRepository
{
    public function __construct(JobTitle $model)
    {
        parent::__construct($model);
    }

    public function getJobTitleList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAllJobTitles(): Collection
    {
        return $this->model->all();
    }

    public function getJobTitle(UuidInterface $id): JobTitle
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createJobTitle(array $data): JobTitle
    {
        return $this->create($data);
    }

    public function updateJobTitle(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteJobTitle(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
