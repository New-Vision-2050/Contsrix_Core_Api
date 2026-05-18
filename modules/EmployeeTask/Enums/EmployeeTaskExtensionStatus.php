<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Enums;

enum EmployeeTaskExtensionStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(string $locale = 'ar'): string
    {
        return match ($this) {
            self::Pending  => $locale === 'ar' ? 'في انتظار مراجعة التمديد' : 'Extension Pending',
            self::Approved => $locale === 'ar' ? 'تم اعتماد التمديد'         : 'Extension Approved',
            self::Rejected => $locale === 'ar' ? 'تم رفض التمديد'            : 'Extension Rejected',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }

    /** Values stored in employee_task_requests.last_extension_status */
    public static function badgeValues(): array
    {
        return [
            'extension_pending'  => self::Pending->label(),
            'extension_approved' => self::Approved->label(),
            'extension_rejected' => self::Rejected->label(),
        ];
    }
}
