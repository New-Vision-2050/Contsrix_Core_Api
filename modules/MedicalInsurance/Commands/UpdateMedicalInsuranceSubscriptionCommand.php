<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateMedicalInsuranceSubscriptionCommand
{
    /**
     * @param array<array{name: string, national_id: string, relation: string, amount: float, subscription_no: ?string}> $familyMembers
     */
    public function __construct(
        private UuidInterface $id,
        private string $userId,
        private string $medicalInsuranceId,
        private float $amount,
        private string $subscriptionNo,
        private int $status = 1,
        private array $familyMembers = [],
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getFamilyMembers(): array
    {
        return $this->familyMembers;
    }

    public function toArray(): array
    {
        return [
            'user_id'              => $this->userId,
            'medical_insurance_id' => $this->medicalInsuranceId,
            'amount'               => $this->amount,
            'subscription_no'      => $this->subscriptionNo,
            'status'               => $this->status,
        ];
    }
}
