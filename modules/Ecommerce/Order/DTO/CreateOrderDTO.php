<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateOrderDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public ?UuidInterface $customerId = null,
        public ?UuidInterface $warehouseId = null,
        public bool $isGuest = false,
        public ?string $customerType = null,
        public string $paymentStatus = 'unpaid',
        public string $orderStatus = 'pending',
        public ?string $paymentMethod = null,
        public float $orderAmount = 0.0,
        public float $paidAmount = 0.0,
        public float $discountAmount = 0.0,
        public float $shippingCost = 0.0,
        public ?string $shippingAddress = null,
        public ?string $orderNote = null,
        public ?string $expectedDeliveryDate = null,
        public array $orderItems = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'customer_id' => $this->customerId?->toString(),
            'warehouse_id' => $this->warehouseId?->toString(),
            'is_guest' => $this->isGuest,
            'customer_type' => $this->customerType,
            'payment_status' => $this->paymentStatus,
            'order_status' => $this->orderStatus,
            'payment_method' => $this->paymentMethod,
            'order_amount' => $this->orderAmount,
            'paid_amount' => $this->paidAmount,
            'discount_amount' => $this->discountAmount,
            'shipping_cost' => $this->shippingCost,
            'shipping_address' => $this->shippingAddress,
            'order_note' => $this->orderNote,
            'expected_delivery_date' => $this->expectedDeliveryDate,
        ];
    }

    public function getOrderItems(): array
    {
        return $this->orderItems;
    }
}
