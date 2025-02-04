<?php

namespace Modules\CompanyUser\Enum;

use App\EnumToArray;

enum CompanyUserRole :int
{
    use EnumToArray;

    case EMPLOYEE = 1;

    case CLIENT = 2;
    case BROKER = 3;


    public  function lang(): string
    {
        return match ($this) {
            self::EMPLOYEE => __('lookups.employee'),
            self::CLIENT => __('lookups.client'),
            self::BROKER => __('lookups.broker'),

        };
    }
}
