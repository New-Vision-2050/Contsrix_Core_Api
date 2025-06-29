<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\DTO;

use Ramsey\Uuid\UuidInterface;

class CreatePackageDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public string $currencyId,
        public string $billingCycle,
        public ?int $trialPeriod,
        public ?string $trialPeriodType,
        public bool $isActive,
        public array $businessTypeIds,
        public array $countryIds,
        public array $programSystemIds
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'currency_id' => $this->currencyId,
            'billing_cycle' => $this->billingCycle,
            'trial_period' => $this->trialPeriod,
            'trial_period_type' => $this->trialPeriodType,
            'is_active' => $this->isActive, 
        ];
    }
}
