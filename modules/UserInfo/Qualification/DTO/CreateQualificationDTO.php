<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateQualificationDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public string $country_id,
        public string $university_id,
        public string $academic_qualification_id,
        public string $academic_specialization_id,
        public int $study_rate,
        public string $graduation_date,
    ) {
    }
    public function getGlobalId(): string
    {
        return $this->global_id;
    }

    // Getter method for other properties
    public function getCompanyId(): string
    {
        return $this->company_id;
    }
    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'country_id' => $this->country_id,
            'university_id' => $this->university_id,
            'academic_qualification_id' => $this->academic_qualification_id,
            'academic_specialization_id' => $this->academic_specialization_id,
            'study_rate' => $this->study_rate,
            'graduation_date' => $this->graduation_date,
        ];
    }
}
