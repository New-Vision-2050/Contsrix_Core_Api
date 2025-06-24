<?php

declare(strict_types=1);

namespace Modules\Subscription\Enums;

enum PeriodUnitEnum: string
{
    case Year = 'year';
    case Month = 'month';
    case Week = 'week';
    case Day = 'day';
    public function label(): string
    {
        return match ($this) {
            self::Year => __('year'),
            self::Month => __('month'),
            self::Week => __('week'),
            self::Day => __('day'),
        };
    }
}
