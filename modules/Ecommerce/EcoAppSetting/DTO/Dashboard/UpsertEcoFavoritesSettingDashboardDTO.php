<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoFavoritesSettingDashboardDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly int $show_favorites_search = 0,
        public readonly int $show_favorites_delete = 0,
        public readonly int $show_favorites_products = 1,
        public readonly string $favorites_display_type = 'list',
        public readonly int $show_favorites_in_app = 1,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'show_favorites_search' => $this->show_favorites_search,
            'show_favorites_delete' => $this->show_favorites_delete,
            'show_favorites_products' => $this->show_favorites_products,
            'favorites_display_type' => $this->favorites_display_type,
            'show_favorites_in_app' => $this->show_favorites_in_app,
        ];
    }
}
