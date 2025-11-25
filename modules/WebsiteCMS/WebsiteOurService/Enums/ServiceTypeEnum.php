<?php

namespace Modules\WebsiteCMS\WebsiteOurService\Enums;

enum ServiceTypeEnum: string
{
    case CARDS = 'cards';
    case HEXA = 'hexa';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::CARDS => 'Cards',
            self::HEXA => 'Hexa',
        };
    }
}
