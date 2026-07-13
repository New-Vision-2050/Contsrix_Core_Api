<?php

declare(strict_types=1);

namespace Modules\Country\Services;

use Illuminate\Support\Collection;
use Modules\Country\DTO\CreateStateDTO;
use Modules\Country\Models\State;
use Modules\Country\Repositories\StateRepository;
use Ramsey\Uuid\UuidInterface;

class StateCRUDService
{
    public function __construct(
        private StateRepository $repository,
    ) {
    }
//new service
    public function create(CreateStateDTO $createStateDTO): State
    {
         return $this->repository->createState($createStateDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            ['flag' => '1'],
            page: $page,
            perPage: $perPage,
        );
    }

    public function getList(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): State
    {
        return $this->repository->getState(
            id: $id,
        );
    }

    public function getStateWithStateWithCity()
    {
        return $this->repository->getStateWithSatesWithCities(request()->State_id,request()->state_id);

    }

    public function getStatesByStateBranch()

    {
       return $this->repository->getStateWithBranchAuthUser();
    }
}
