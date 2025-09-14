<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoDiscountDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $code = null,
        public string $type = 'percentage',
        public float $value = 0,
        public ?float $min_order_amount = null,
        public ?float $max_discount_amount = null,
        public ?int $usage_limit = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public bool $is_active = true,
        public string $applies_to = 'all_products',
        public array $product_ids = [],
        public string $type_discount = 'code',
        public string $priority = 'basic',
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name ?: $this->generateName(),
            'description' => $this->description ?: $this->generateDescription(),
            'code' => $this->code ?: $this->generateCode(),
            'type' => $this->type,
            'value' => $this->value,
            'min_order_amount' => $this->min_order_amount ?: $this->getDefaultMinOrderAmount(),
            'max_discount_amount' => $this->max_discount_amount ?: $this->getDefaultMaxDiscountAmount(),
            'usage_limit' => $this->usage_limit,
            'start_date' => $this->start_date ?: $this->getDefaultStartDate(),
            'end_date' => $this->end_date ?: $this->getDefaultEndDate(),
            'is_active' => $this->is_active,
            'applies_to' => $this->applies_to,
            'product_ids' => $this->product_ids,
            'type_discount' => $this->type_discount,
            'priority' => $this->priority,
        ];
    }

    private function generateCode(): string
    {
        return 'DISC' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    private function generateName(): string
    {
        $typeText = $this->type === 'percentage' ? $this->value . '%' : $this->value . ' ريال';
        
        $discountTypeText = match($this->type_discount) {
            'order' => 'خصم الطلب',
            'time' => 'خصم وقتي',
            'package' => 'خصم الباقة',
            default => 'كود خصم'
        };

        $priorityText = match($this->priority) {
            'vip' => 'VIP',
            'premium' => 'عميل مميز',
            default => 'اساسية'
        };

        return $discountTypeText . ' ' . $priorityText . ' - ' . $typeText;
    }

    private function generateDescription(): string
    {
        $typeText = $this->type === 'percentage' ? 'خصم بنسبة ' . $this->value . '%' : 'خصم بقيمة ' . $this->value . ' ريال';
        
        $discountTypeText = match($this->type_discount) {
            'order' => 'يطبق على الطلب',
            'time' => 'يطبق لفترة محدودة',
            'package' => 'يطبق على الباقة',
            default => 'يطبق بالكود'
        };

        $priorityText = match($this->priority) {
            'vip' => 'للعملاء المميزين VIP',
            'premium' => 'للعملاء المميزين',
            default => 'للجميع'
        };

        return $typeText . ' - ' . $discountTypeText . ' - ' . $priorityText;
    }

    private function getDefaultMinOrderAmount(): float
    {
        // Default minimum order amount based on priority and discount type
        $baseAmount = $this->type === 'percentage' ? 100.0 : $this->value * 2;
        
        return match($this->priority) {
            'vip' => $baseAmount * 2, // VIP customers need higher minimum orders
            'premium' => $baseAmount * 1.5, // Premium customers moderate minimum
            default => $baseAmount // Basic customers standard minimum
        };
    }

    private function getDefaultMaxDiscountAmount(): ?float
    {
        // Only set max discount for percentage type
        if ($this->type === 'percentage') {
            $baseMax = $this->value * 10;
            
            return match($this->priority) {
                'vip' => $baseMax * 2, // VIP gets higher max discount
                'premium' => $baseMax * 1.5, // Premium gets moderate increase
                default => $baseMax // Basic gets standard max
            };
        }

        return null; // No max limit for fixed amount discounts
    }

    private function getDefaultUsageLimit(): int
    {
        // Default usage limit based on discount type
        return match($this->type_discount) {
            'order' => 1000,
            'time' => 500,
            'package' => 200,
            default => 100
        };
    }

    private function getDefaultStartDate(): string
    {
        // Start immediately
        return now()->format('Y-m-d H:i:s');
    }

    private function getDefaultEndDate(): string
    {
        // Different end dates based on discount type
        return match($this->type_discount) {
            'time' => now()->addDays(7)->format('Y-m-d H:i:s'), // Time discounts last 1 week
            'order' => now()->addMonths(6)->format('Y-m-d H:i:s'), // Order discounts last 6 months
            'package' => now()->addMonths(12)->format('Y-m-d H:i:s'), // Package discounts last 1 year
            default => now()->addMonths(3)->format('Y-m-d H:i:s') // Code discounts last 3 months
        };
    }
}
