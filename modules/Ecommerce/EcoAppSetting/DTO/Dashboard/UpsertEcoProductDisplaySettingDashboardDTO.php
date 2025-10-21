<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoProductDisplaySettingDashboardDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly string $product_display_category = 'latest',
        public readonly string $product_display_type = 'list',
        public readonly int $product_columns_count = 2,
        public readonly int $product_rows_count = 8,
        public readonly int $show_products_in_app = 1,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'product_display_category' => $this->product_display_category,
            'product_display_type' => $this->product_display_type,
            'product_columns_count' => $this->product_columns_count,
            'product_rows_count' => $this->product_rows_count,
            'show_products_in_app' => $this->show_products_in_app,
        ];
    }
}
