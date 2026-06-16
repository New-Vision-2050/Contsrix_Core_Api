<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Modules\EmployeeTask\DTO\CreateEmployeeTaskItemDTO;
use Modules\EmployeeTask\Models\EmployeeTaskItem;
use Modules\EmployeeTask\Repositories\EmployeeTaskItemRepository;
use Illuminate\Database\Eloquent\Collection;

class EmployeeTaskItemService
{
    public function __construct(private readonly EmployeeTaskItemRepository $repository) {}

    public function list(array $filters = []): Collection
    {
        return $this->repository->list($filters);
    }


}
