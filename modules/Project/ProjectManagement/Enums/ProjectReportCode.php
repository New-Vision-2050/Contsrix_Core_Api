<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Enums;

enum ProjectReportCode: string
{
    case Jeddah = 'jeddah';
    case Makkah = 'makkah';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $code): string => $code->value, self::cases());
    }

    public static function default(): self
    {
        return self::Jeddah;
    }
}
