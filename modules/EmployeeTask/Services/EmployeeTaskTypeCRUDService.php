<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Modules\EmployeeTask\DTO\CreateEmployeeTaskTypeDTO;
use Modules\EmployeeTask\Models\EmployeeTaskType;
use Modules\EmployeeTask\Repositories\EmployeeTaskTypeRepository;
use Illuminate\Database\Eloquent\Collection;

class EmployeeTaskTypeCRUDService
{
    public function __construct(private readonly EmployeeTaskTypeRepository $repository) {}

    public function list(array $filters = []): Collection
    {
        return $this->repository->list($filters);
    }

    public function create(CreateEmployeeTaskTypeDTO $dto): EmployeeTaskType
    {
        return $this->repository->create($dto->toArray());
    }

    public function get(string $id): EmployeeTaskType
    {
        $type = $this->repository->findById($id);
        if (!$type) throw new \Exception('Task type not found', 404);
        return $type;
    }

    public function update(string $id, array $data): EmployeeTaskType
    {
        return $this->repository->update($this->get($id), $data);
    }
}
