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

    public function withoutScopePaginated(array $conditions = [], $page = 1, $perPage = 10, ?string $sort = null,string $order = 'asc')
    {
        $query = $this->model->withoutGlobalScope("active")->where($conditions)->filter(request()->all());


        if ($sort) {
            switch ($sort) {
                case 'job_type.name':
                    $query->join('job_types', 'job_titles.job_type_id', '=', 'job_types.id')
                        ->orderBy('job_types.name', $order)
                        ->select('job_titles.*');
                    break;

                case 'user_count':
                    $query->withCount('users')->orderBy('users_count', $order);
                    break;

                case 'name':
                    $query->join('translations', function ($join) {
                            $join->on('translations.translatable_id', '=', 'job_titles.id')
                                ->where('translations.translatable_type', $this->model::class)
                                ->where('translations.field', '=', 'name')
                                ->where('translations.locale', '=', app()->getLocale());
                        })
                        ->orderBy('translations.content', $order)
                        ->select('job_titles.*');
                    break;

                case 'status':
                    $query->orderBy($sort, $order);
                    break;

                default:
                    // ignore or throw exception for unknown sort
                    break;
            }
        }

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        return array_merge($paginationArray, [
            'data' => $paginatedData
        ]);
    }

    public function getJobTitleList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }


    public function getAllJobTitles(): Collection
    {

        return $this->model->filter(request()->all())->get();
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

        if (isset($filters['ids']) && is_array($filters['ids']) && count($filters["ids"])) {
            $query->whereIn('id', $filters['ids']);
        }

        return $query->with(['jobType'])->get();
    }
}
