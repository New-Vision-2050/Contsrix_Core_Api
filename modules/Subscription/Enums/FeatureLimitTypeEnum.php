<?php

declare(strict_types=1);

namespace Modules\Subscription\Enums;

enum FeatureLimitTypeEnum: string
{
    case PerDay = 'per_day';
    case PerMonth = 'per_month';
    case PerUser = 'per_user';
    case PerPackage = 'per_package';
    case GB = 'gb';
    case MB = 'mb';
    case Minutes = 'minutes';

    /**
     * Get all values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the limit type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PerDay => 'Per Day',
            self::PerMonth => 'Per Month',
            self::PerUser => 'Per User',
            self::PerPackage => 'Per Package',
            self::GB => 'Gigabytes',
            self::MB => 'Megabytes',
            self::Minutes => 'Minutes',
        };
    }
}
