<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoOrderDetailDTO
{
    public function __construct(
        public string $eco_order_id,
        public string $eco_product_id,
        public ?string $shipping_method_id = null,
        public ?string $warehouse_id = null,
        public ?string $digital_file_after_sell = null,
        public ?array $product_details = null,
        public int $qty = 1,
        public float $price = 0,
        public float $tax = 0,
        public float $discount = 0,
        public string $tax_model = 'exclude',
        public string $delivery_status = 'pending',
        public string $payment_status = 'unpaid',
        public ?string $variant = null,
        public ?array $variation = null,
        public string $discount_type = 'amount',
        public bool $is_stock_decreased = false,
        public int $refund_request = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'eco_order_id' => $this->eco_order_id,
            'eco_product_id' => $this->eco_product_id,
            'shipping_method_id' => $this->shipping_method_id,
            'warehouse_id' => $this->warehouse_id,
            'digital_file_after_sell' => $this->digital_file_after_sell,
            'product_details' => $this->product_details,
            'qty' => $this->qty,
            'price' => $this->price,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'tax_model' => $this->tax_model,
            'delivery_status' => $this->delivery_status,
            'payment_status' => $this->payment_status,
            'variant' => $this->variant,
            'variation' => $this->variation,
            'discount_type' => $this->discount_type,
            'is_stock_decreased' => $this->is_stock_decreased,
            'refund_request' => $this->refund_request,
        ];
    }
}
