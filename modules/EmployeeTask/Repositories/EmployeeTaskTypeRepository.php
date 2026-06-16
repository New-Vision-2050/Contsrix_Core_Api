<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Repositories;

use Modules\EmployeeTask\Models\EmployeeTaskType;
use Illuminate\Database\Eloquent\Collection;

class EmployeeTaskTypeRepository
{
    public function list(array $filters = []): Collection
    {
        return EmployeeTaskType::filter($filters)->get();
    }

    public function create(array $data): EmployeeTaskType
    {
        return EmployeeTaskType::create($data);
    }

    public function findById(string $id): ?EmployeeTaskType
    {
        return EmployeeTaskType::find($id);
    }

    public function update(EmployeeTaskType $type, array $data): EmployeeTaskType
    {
        $type->update($data); return $type->fresh();
    }
}
