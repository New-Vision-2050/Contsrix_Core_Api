<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\DTO;

class CreateMedicalInsuranceSubscriptionFamilyMemberDTO
{
    public function __construct(
        public string $name,
        public string $nationalId,
        public string $relation,
        public float $amount,
        public ?string $subscriptionNo = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name'            => $this->name,
            'national_id'     => $this->nationalId,
            'relation'        => $this->relation,
            'amount'          => $this->amount,
            'subscription_no' => $this->subscriptionNo,
        ];
    }
}
