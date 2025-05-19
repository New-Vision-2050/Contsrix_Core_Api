<?php

declare(strict_types=1);

namespace Modules\JobTitle\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\UuidInterface;
use Modules\JobTitle\Models\JobTitle;

/**
 * @property JobTitle $model
 * @method JobTitle findOneOrFail($id)
 * @method JobTitle findOneByOrFail(array $data)
 */
class JobTitleRepository extends BaseRepository
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function __construct(JobTitle $model)
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

    public function getJobTitleList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }



public function getAllJobTitles(): Collection
{
    return $this->model->get();
}


    public function getJobTitle(UuidInterface $id): JobTitle
    {
        return $this->model->withoutGlobalScope("active")->where('id', $id)->first();
    }

    public function createJobTitle(array $data): JobTitle
    {
        return $this->create($data);
    }

    public function updateJobTitle(UuidInterface $id, array $data): bool
    {
        return $this->model->withoutGlobalScope("active")->where('id', $id)->first()->update($data);
    }

    public function deleteJobTitle(UuidInterface $id): bool
    {
        return $this->model->withoutGlobalScope("active")->where('id', $id)->first()->delete($id);
    }

    /**
     * Get filtered job titles for export
     *
     * @param array $filters Array of filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->withoutGlobalScope("active");

        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        return $query->with(['jobType'])->get();
    }
}
