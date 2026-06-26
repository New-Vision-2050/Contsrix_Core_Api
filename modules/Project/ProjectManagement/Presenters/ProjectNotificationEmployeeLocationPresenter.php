<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

class ProjectNotificationEmployeeLocationPresenter
{
    public static function collection(array $employees): array
    {
        return $employees;
    }
}
