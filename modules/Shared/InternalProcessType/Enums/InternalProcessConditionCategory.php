<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessConditionCategory: string
{
    case Time        = 'time';
    case Location    = 'location';
    case Attendance  = 'attendance';
    case TaskStatus  = 'task_status';
    case OpenTask    = 'open_task';
    case Shift       = 'shift';
    case Duration    = 'duration';
    case Attachment  = 'attachment';

    public function labelAr(): string
    {
        return match ($this) {
            self::Time        => 'وقت',
            self::Location    => 'موقع',
            self::Attendance  => 'حضور',
            self::TaskStatus  => 'حالة المهمة',
            self::OpenTask    => 'مهمة مفتوحة',
            self::Shift       => 'دوام',
            self::Duration    => 'مدة',
            self::Attachment  => 'مرفقات',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
