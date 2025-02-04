<?php

namespace Modules\CompanyUser\Enum;

use App\EnumToArray;

enum CompanyUserStatus :int
{
    use EnumToArray;

    case ACTIVE = 1;

    case INACTIVE = 0;
    case PENDING = -1;


    public  function lang(): string
    {
        return match ($this) {
            self::ACTIVE => __('lookups.active'),
            self::INACTIVE => __('lookups.inactive'),
            self::PENDING => __('lookups.pending'),

        };
    }
}
