<?php declare(strict_types=1);

namespace Modules\Subscription\Package\DTO;

use Modules\Subscription\Enums\PeriodUnitEnum;

class CreatePackageDTO
{
    /**
     * @param string $name
     * @param array<string> $countries
     * @param array<string> $companyFields
     * @param array<string> $companyTypes
     */
    public function __construct(
        public string $companyAccessProgramId,
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
            'company_access_program_id' => $this->companyAccessProgramId,
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
