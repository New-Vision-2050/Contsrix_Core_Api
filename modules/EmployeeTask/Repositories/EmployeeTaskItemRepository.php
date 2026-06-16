<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Repositories;

use Modules\EmployeeTask\Models\EmployeeTaskItem;
use Illuminate\Database\Eloquent\Collection;

class EmployeeTaskItemRepository
{
    public function list(array $filters = []): Collection
    {
        return EmployeeTaskItem::filter($filters)->get();
    }
}
