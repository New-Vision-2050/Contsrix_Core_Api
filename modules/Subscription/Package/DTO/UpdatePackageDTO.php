<?php declare(strict_types=1);

namespace Modules\Subscription\Package\DTO;

use Modules\Subscription\Enums\PeriodUnitEnum;

class UpdatePackageDTO
{
    /**
     * @param string $packageId
     * @param string $name
     * @param array<string> $countries
     * @param array<string> $companyFields
     * @param array<string> $companyTypes
     */
    public function __construct(
        public string $packageId,
        public string $name,
        public float $price,
        public string $currency,
        public int $subscriptionPeriod,
        public PeriodUnitEnum $subscriptionPeriodUnit,
        public ?int $trialPeriod = null,
        public ?PeriodUnitEnum $trialPeriodUnit = null,
        public array $countries = [],
        public array $companyFields = [],
        public array $companyTypes = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $this->currency,
            'subscription_period' => $this->subscriptionPeriod,
            'subscription_period_unit' => $this->subscriptionPeriodUnit->value,
            'trial_period' => $this->trialPeriod,
            'trial_period_unit' => $this->trialPeriodUnit?->value,
            'countries' => $this->countries,
            'company_fields' => $this->companyFields,
            'company_types' => $this->companyTypes,
        ];
    }
}
