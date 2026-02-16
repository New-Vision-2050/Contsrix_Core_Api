<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateMedicalInsuranceDTO
{
    public function __construct(
        public string $name,
        public string $policyNumber,
        public string $employeeId,
        public ?string $endDate = null,
        public int $status = 1,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'policy_number' => $this->policyNumber,
            'employee_id' => $this->employeeId,
            'end_date' => $this->endDate,
            'company_id' => tenant('id'),
            'status' => $this->status,
        ];
    }
}
