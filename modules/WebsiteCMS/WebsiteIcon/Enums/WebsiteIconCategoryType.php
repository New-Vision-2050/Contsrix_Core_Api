<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Enums;

enum WebsiteIconCategoryType: string
{
    case CERTIFICATES = 'certificates';
    case APPROVALS = 'approvals';
    case COMPANIES = 'companies';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::CERTIFICATES => __('lookups.certificates'),
            self::APPROVALS => __('lookups.approvals'),
            self::COMPANIES => __('lookups.companies'),
        };
    }
}
