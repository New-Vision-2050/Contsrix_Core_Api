<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Enums;

enum SocialMediaType: string
{
    case FACEBOOK = 'facebook';
    case LINKEDIN = 'linkedin';
    case X = 'twitter';
    case YOUTUBE = 'youtube';
    case INSTAGRAM = 'instagram';
    case TIKTOK = 'tiktok';
    case SNAPCHAT = 'snapchat';
    case WHATSAPP = 'whatsapp';
    case TELEGRAM = 'telegram';
    case PINTEREST = 'pinterest';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function label($value): string
    {
        return match ($value) {
            self::FACEBOOK => 'Facebook',
            self::LINKEDIN => 'LinkedIn',
            self::X => 'X (Twitter)',
            self::YOUTUBE => 'YouTube',
            self::INSTAGRAM => 'Instagram',
            self::TIKTOK => 'TikTok',
            self::SNAPCHAT => 'Snapchat',
            self::WHATSAPP => 'WhatsApp',
            self::TELEGRAM => 'Telegram',
            self::PINTEREST => 'Pinterest',
        };
    }
}
