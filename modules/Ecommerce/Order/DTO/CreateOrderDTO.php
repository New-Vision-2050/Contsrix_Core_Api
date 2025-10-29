<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateOrderDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public ?string $customerId = null,
        public bool $isGuest = false,
        public string $paymentMethod,
        public string $shippingAddress,
        public ?string $orderNote = null,
        public array $orderItems = [],
        public ?string $customerName = null,
        public ?string $customerPhone = null,
        public ?string $customerEmail = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'customer_id' => $this->customerId,
            'is_guest' => $this->isGuest,
            'payment_method' => $this->paymentMethod,
            'shipping_address' => $this->shippingAddress,
            'order_note' => $this->orderNote,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'customer_email' => $this->customerEmail,
        ];
    }

    public function getOrderItems(): array
    {
        return $this->orderItems;
    }
}
