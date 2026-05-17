<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\DTO;

class CreateMedicalInsuranceSubscriptionDTO
{
    /**
     * @param array<CreateMedicalInsuranceSubscriptionFamilyMemberDTO> $familyMembers
     */
    public function __construct(
        public string $userId,
        public string $medicalInsuranceId,
        public float $amount,
        public string $subscriptionNo,
        public int $status = 1,
        public array $familyMembers = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'user_id'              => $this->userId,
            'medical_insurance_id' => $this->medicalInsuranceId,
            'amount'               => $this->amount,
            'subscription_no'      => $this->subscriptionNo,
            'status'               => $this->status,
            'company_id'           => tenant('id'),
        ];
    }
}
