<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO;

class CreateCompanyDTO
{
    public function __construct(
        public string $name,
        public string $userName,
        private string $email,
        private string $serialNo,
        private string $phone,
        private string $countryId,
        private string $companyTypeId,
        private string $companyFieldId,
        private string $registrationTypeId,
        private string $registrationNo,
        private string $generalManagerId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'user_name' => $this->userName,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_id' => $this->countryId,
            'company_type_id' => $this->companyTypeId,
            'company_field_id' => $this->companyFieldId,
            'general_manager_id' => $this->generalManagerId,
            'registration_type_id' => $this->registrationTypeId,
            'registration_no' => $this->registrationNo,
            'serial_no' => $this->serialNo,
        ];
    }
}
