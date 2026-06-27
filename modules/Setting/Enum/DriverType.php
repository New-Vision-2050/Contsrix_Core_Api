<?php

namespace Modules\Setting\Enum;

use App\EnumToArray;

enum DriverType: string
{
    use EnumToArray;

    case EMAIL = "email";
    case SMS = "sms";
    case WHATSAPP = "whatsapp";
    case SOCIAL = "social";


    public static function lang($value): string
    {
        return match ($value) {
            self::EMAIL->value => __('lookups.email'),
            self::SMS->value => __('lookups.sms'),
            self::WHATSAPP->value => __('lookups.whatsapp'),
            self::SOCIAL->value => __('lookups.social'),
        };
    }
}
