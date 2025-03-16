<?php

namespace Modules\Setting\Enum;

use App\EnumToArray;

enum DriverType: string
{
    use EnumToArray;

    case EMAIL = "email";
    case SMS = "sms";
    case SOCIAL = "social";


    public static function lang($value): string
    {
        return match ($value) {
            self::EMAIL->value => __('lookups.email'),
            self::SMS->value => __('lookups.sms'),
            self::SOCIAL->value => __('lookups.social'),
        };
    }
}
