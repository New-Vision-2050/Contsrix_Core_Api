<?php

declare(strict_types=1);

namespace Modules\Company\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyDTO
{
    public function __construct(
        public string $name,
        private string $email,
        private string $phone,
        private string $country_id,
        private string $company_type_id,
        private string $company_field_id,
        private string $registration_type_id,
        private string $registration_no,
        private string $general_manager_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_id' => $this->country_id,
            'company_type_id' => $this->company_type_id,
            'company_field_id' => $this->company_field_id,
            'registration_type_id' => $this->registration_type_id,
            'registration_no' => $this->registration_no,
            'general_manager_id' => $this->general_manager_id
        ];
    }
}
