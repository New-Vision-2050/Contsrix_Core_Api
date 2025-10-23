<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoFilterSettingDashboardDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly array $filters = [],
        public readonly int $show_filter_in_app = 1,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'show_filter_in_app' => $this->show_filter_in_app,
        ];
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}
