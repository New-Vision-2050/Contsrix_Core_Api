<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessConditionType: string
{
    case Bool   = 'bool';
    case Int    = 'int';
    case String = 'string';
    case Time   = 'time';
    case Select = 'select';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
