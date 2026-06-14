<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessEntityType: string
{
    case EmployeeTask = 'employee_task';
    case LeaveRequest = 'leave_request';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
