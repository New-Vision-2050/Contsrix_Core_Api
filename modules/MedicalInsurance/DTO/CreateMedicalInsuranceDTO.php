<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\DTO;

class CreateMedicalInsuranceDTO
{
    public function __construct(
        public string $name,
        public string $policyNumber,
        public ?string $provider = null,
        public ?string $employeeId = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?float $value = null,
        public ?int $individualsCount = null,
        public int $status = 1,
        public array $attachments = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'policy_number' => $this->policyNumber,
            'provider' => $this->provider,
            'employee_id' => $this->employeeId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'value' => $this->value,
            'individuals_count' => $this->individualsCount,
            'company_id' => tenant('id'),
            'status' => $this->status,
        ];
    }
}
