<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoAppSettingThemeDashboardDTO
{
    public function __construct(
        public UuidInterface $company_id,
        public string $background_color = '#1e1b4b',
        public bool $enable_search = true,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'background_color' => $this->background_color,
            'enable_search' => $this->enable_search,
        ];
    }
}
