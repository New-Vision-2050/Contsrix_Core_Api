<?php

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Enum;

use App\EnumToArray;

enum CategoryWebsiteCMSType: int
{
    use EnumToArray;

    case SERVICES = 1;



    public static function lang($value): string
    {
        return match ((int)$value) {
            self::SERVICES->value => __('lookups.services'),


        };
    }
}
