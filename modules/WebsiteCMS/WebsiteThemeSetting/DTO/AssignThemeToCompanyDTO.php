<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\DTO;

use Ramsey\Uuid\UuidInterface;

class AssignThemeToCompanyDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly UuidInterface $website_theme_setting_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'website_theme_setting_id' => $this->website_theme_setting_id->toString(),
        ];
    }
}
