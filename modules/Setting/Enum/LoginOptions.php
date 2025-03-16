<?php

namespace Modules\Setting\Enum;

use App\EnumToArray;

enum LoginOptions: int
{
    use EnumToArray;

    case PASSWORD = 1;
    case OTP = 2;
    case BARCODE = 3;



    public static function lang($value): string
    {
        return match ((int)$value) {
            self::PASSWORD->value => __('lookups.password'),
            self::OTP->value => __('lookups.otp'),
        };
    }
}
