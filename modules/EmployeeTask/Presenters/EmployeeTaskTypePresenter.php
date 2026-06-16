<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\EmployeeTask\Models\EmployeeTaskType;

final class EmployeeTaskTypePresenter
{
    public static function single(EmployeeTaskType $type): array
    {
        return [
            'id'   => $type->id,
            'key'  => $type->key,
            'name' => $type->name,
        ];
    }

    public static function collection(iterable $types): array
    {
        return collect($types)->map(fn($t) => self::single($t))->all();
    }
}
