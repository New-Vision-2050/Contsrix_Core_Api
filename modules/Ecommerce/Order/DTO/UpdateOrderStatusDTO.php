<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\DTO;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UpdateOrderStatusDTO
{
    public function __construct(
        private UuidInterface $orderId,
        private string $orderStatus,
        private ?string $paymentStatus = null,
        private ?string $reason = null,
        private ?string $notes = null,
    ) {
    }

    public function getOrderId(): UuidInterface
    {
        return $this->orderId;
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId->toString(),
            'order_status' => $this->orderStatus,
            'payment_status' => $this->paymentStatus,
            'reason' => $this->reason,
            'notes' => $this->notes,
        ];
    }
}
