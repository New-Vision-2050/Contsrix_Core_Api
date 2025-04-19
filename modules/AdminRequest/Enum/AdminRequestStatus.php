<?php

namespace Modules\AdminRequest\Enum;

use App\EnumToArray;

enum AdminRequestStatus: int
{
    use EnumToArray;

    case ACTIVE = 1;
    case INACTIVE = 0;
    case PENDING = -1;


    public static function lang($value): string
    {
        return match ((int)$value) {
            self::ACTIVE->value => __('lookups.active'),
            self::INACTIVE->value => __('lookups.inactive'),
            self::PENDING->value => __('lookups.pending'),

        };
    }
}
