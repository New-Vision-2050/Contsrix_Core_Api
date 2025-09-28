<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoFilterDisplaySettingDashboardDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly int $show_filter_in_app = 1,
        public readonly int $show_category_filter = 1,
        public readonly int $show_product_filter = 1,
        public readonly int $show_color_filter = 1,
        public readonly int $show_brand_filter = 1,
        public readonly int $show_size_filter = 1,
        public readonly int $show_price_filter = 1,
        public readonly int $show_rating_filter = 1,
        public readonly int $show_discount_filter = 1,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'show_filter_in_app' => $this->show_filter_in_app,
            'show_category_filter' => $this->show_category_filter,
            'show_product_filter' => $this->show_product_filter,
            'show_color_filter' => $this->show_color_filter,
            'show_brand_filter' => $this->show_brand_filter,
            'show_size_filter' => $this->show_size_filter,
            'show_price_filter' => $this->show_price_filter,
            'show_rating_filter' => $this->show_rating_filter,
            'show_discount_filter' => $this->show_discount_filter,
        ];
    }
}
