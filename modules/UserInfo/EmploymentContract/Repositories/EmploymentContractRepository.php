<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Country\Repositories\CountryRepository;
use Modules\Country\Repositories\StateRepository;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;

/**
 * @property EmploymentContract $model
 * @method EmploymentContract findOneOrFail($id)
 * @method EmploymentContract findOneByOrFail(array $data)
 */
class EmploymentContractRepository extends BaseRepository
{
    public function __construct(EmploymentContract $model,private StateRepository $stateRepository)
    {
        parent::__construct($model);
    }

    public function getEmploymentContractList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEmploymentContract(UuidInterface $companyId, UuidInterface $globalId): ?EmploymentContract
    {
        return $this->model->where([
            'global_id' => $globalId,
            'company_id' => $companyId,
        ])->first();
    }

    public function createEmploymentContract(array $data): EmploymentContract
    {
       $state = $this->stateRepository->findOneBy(["id" =>$data['state_id']]);
       $data["country_id"] = $state->country_id;

        $employmentContract = $this->model->where([
            'global_id' => $data['global_id'],
            'company_id' => $data['company_id'],
        ])->first();

        if ($employmentContract) {
            $employmentContract->update($data);
            return $employmentContract;
        }

        return $this->model->create($data);
    }

    public function updateEmploymentContract(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEmploymentContract(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
