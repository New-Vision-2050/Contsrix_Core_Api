<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoProductCardSettingDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly int $show_product_name = 0,
        public readonly int $show_product_description_card = 1,
        public readonly int $show_product_price_card = 1,
        public readonly int $show_product_color = 1,
        public readonly int $show_product_size_card = 1,
        public readonly int $show_similar_products_card = 1,
        public readonly string $product_card_display_type = 'list',
        public readonly int $product_card_columns_count = 2,
        public readonly int $show_discount_code = 1,
        public readonly int $show_payment_details = 1,
        public readonly int $show_product_card_in_app = 1,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'show_product_name' => $this->show_product_name,
            'show_product_description_card' => $this->show_product_description_card,
            'show_product_price_card' => $this->show_product_price_card,
            'show_product_color' => $this->show_product_color,
            'show_product_size_card' => $this->show_product_size_card,
            'show_similar_products_card' => $this->show_similar_products_card,
            'product_card_display_type' => $this->product_card_display_type,
            'product_card_columns_count' => $this->product_card_columns_count,
            'show_discount_code' => $this->show_discount_code,
            'show_payment_details' => $this->show_payment_details,
            'show_product_card_in_app' => $this->show_product_card_in_app,
        ];
    }
}
