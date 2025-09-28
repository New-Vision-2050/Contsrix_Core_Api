<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoCartSettingDashboardDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly int $show_cart_products = 0,
        public readonly string $cart_display_type = 'list',
        public readonly int $cart_columns_count = 2,
        public readonly int $show_cart_in_app = 1,
        private mixed $empty_cart_image = null,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'show_cart_products' => $this->show_cart_products,
            'cart_display_type' => $this->cart_display_type,
            'cart_columns_count' => $this->cart_columns_count,
            'show_cart_in_app' => $this->show_cart_in_app,
        ];
    }

    public function getEmptyCartImage(): mixed {
        return $this->empty_cart_image;
    }
}
