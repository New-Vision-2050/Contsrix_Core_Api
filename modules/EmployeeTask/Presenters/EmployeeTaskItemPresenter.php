<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\EmployeeTask\Models\EmployeeTaskItem;

final class EmployeeTaskItemPresenter
{
    public static function single(EmployeeTaskItem $item): array
    {
        return [
            'id'   => $item->id,
            'key'  => $item->key,
            'name' => $item->name,
            'model_class' => $item->model_class,
        ];
    }

    public static function collection(iterable $items): array
    {
        return collect($items)->map(fn($i) => self::single($i))->all();
    }
}
