<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\DTO;

use Modules\SubscriptionSystem\Subscription\Enums\PackageBillingCycleEnum;

class CreatePackageDTO
{
    /**
     * @param PackageModuleDTO[] $modules
     */
    public function __construct(
        public array $name,
        public float $price,
        public PackageBillingCycleEnum $billing_cycle,
        public bool $is_active = true,
        public array $modules = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'billing_cycle' => $this->billing_cycle->value,
            'is_active' => $this->is_active,
            'modules' => array_map(fn(PackageModuleDTO $m) => $m->toArray(), $this->modules),
        ];
    }
}
