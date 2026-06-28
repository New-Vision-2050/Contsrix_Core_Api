<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO;

class CreateCompanyDTO
{
    public function __construct(
        public string $name,
        public string $userName,
        // private string $email,
        // private string $serialNo,
        // private string $phone,
        public string $countryId,
        // public string $companyTypeId,
        public array $companyFieldId,
        // public string $registrationTypeId,
        // public string $registrationNo,
        public string $generalManagerId,
        public $isBroker = 0,

        public $isClient = 0,
        public bool $isDraft = false
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => ["ar"=>$this->name,"en"=>$this->name],
            'user_name' => $this->userName,
            'country_id' => $this->countryId,
            // 'company_field_id' => $this->companyFieldId,//TODO Fix this
            'general_manager_id' => $this->generalManagerId,
            "is_client" => $this->isClient,
            "is_draft" => $this->isDraft,
            "is_broker" => $this->isBroker,
        ];
    }
}
