<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\DTO;

class CreateMedicalInsuranceCategoryDTO
{
    public function __construct(
        public string $medicalInsuranceId,
        public string $name,
        public float $coverageLimit,
        public string $description,
        public ?string $type = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'medical_insurance_id' => $this->medicalInsuranceId,
            'name'                 => $this->name,
            'type'                 => $this->type,
            'coverage_limit'       => $this->coverageLimit,
            'description'          => $this->description,
            'company_id'           => tenant('id'),
        ];
    }
}
