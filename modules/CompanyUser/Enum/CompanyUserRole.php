<?php

namespace Modules\CompanyUser\Enum;

use App\EnumToArray;

enum CompanyUserRole: int
{
    use EnumToArray;

    case EMPLOYEE = 1;

    case CLIENT = 2;
    case BROKER = 3;


    public  static function lang($value): string
    {
        return match ((int)$value) {
            self::EMPLOYEE->value => __('lookups.employee'),
            self::CLIENT->value => __('lookups.client'),
            self::BROKER->value => __('lookups.broker'),

        };
    }
}
