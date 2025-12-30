<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\JobType\Models\JobType;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
/**
 * @property JobType $model
 * @method JobType findOneOrFail($id)
 * @method JobType findOneByOrFail(array $data)
 */
class JobTypeRepository extends BaseRepository
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function __construct(JobType $model)
    {
        parent::__construct($model);
    }

    public function withoutScopePaginated(array $conditions=[], $page=1, $perPage=10)
    {
         $query = $this->model->withoutGlobalScope("active")->where($conditions)->filter(request()->all())->orderBy("created_at", "desc");
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
        return $this->model->filter(request()->all())->get();

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
        $jobType = $this->model->withoutGlobalScope("active")->where('id', $id)->first();
        if(count($jobType->jobTitles) > 0){
           throw  new CustomException(__("validation.delete-not-allowed"), 400);
        }
        return $jobType->delete($id);
    }

    /**
     * Get filtered job types for export
     *
     * @param array $filters Array of filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->withoutGlobalScope("active");

        if (isset($filters['ids']) && is_array($filters['ids']) && count($filters["ids"])) {
            $query->whereIn('id', $filters['ids']);
        }

        // Include the job titles relationship to display count
        return $query->with(['jobTitles', 'userProfissional'])->get();
    }
}
