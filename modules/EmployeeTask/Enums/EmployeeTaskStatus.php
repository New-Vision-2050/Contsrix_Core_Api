<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Enums;

enum EmployeeTaskStatus: string
{
    case Pending    = 'pending';
    case Approved   = 'approved';
    case Rejected   = 'rejected';
    case InProgress = 'in_progress';
    case Paused     = 'paused';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(string $locale = 'ar'): string
    {
        return match ($this) {
            self::Pending    => $locale === 'ar' ? 'في انتظار الاعتماد' : 'Pending Approval',
            self::Approved   => $locale === 'ar' ? 'معتمدة'             : 'Approved',
            self::Rejected   => $locale === 'ar' ? 'مرفوضة'             : 'Rejected',
            self::InProgress => $locale === 'ar' ? 'جارية'              : 'In Progress',
            self::Paused     => $locale === 'ar' ? 'متوقفة مؤقتاً'      : 'Paused',
            self::Completed  => $locale === 'ar' ? 'مكتملة'             : 'Completed',
            self::Cancelled  => $locale === 'ar' ? 'ملغية'              : 'Cancelled',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }

    /** Statuses where the employee can still actively work on the task. */
    public static function activeStatuses(): array
    {
        return [self::InProgress->value, self::Paused->value];
    }

    /** Statuses that are terminal (task can never be re-opened). */
    public static function terminalStatuses(): array
    {
        return [self::Completed->value, self::Cancelled->value, self::Rejected->value];
    }
}
