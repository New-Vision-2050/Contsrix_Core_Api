<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Enums;

enum ProjectRequirementRepetition: string
{
    case Once = 'once';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $repetition): string => $repetition->value, self::cases());
    }

    public static function intervalTypeFor(string $repetition): ?string
    {
        return match ($repetition) {
            self::Daily->value => 'day',
            self::Weekly->value => 'week',
            self::Monthly->value => 'month',
            default => null,
        };
    }
}
