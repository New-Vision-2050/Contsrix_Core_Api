<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class UpdateOfficialCompanyDataRequestDTO
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $countryId,
        private string $companyTypeId,
        private string $companyFieldId,

    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }


    public function getCountryId(): ?string
    {
        return $this->countryId;
    }
    public function getCompanyTypeId(): ?string
    {
        return $this->companyTypeId;
    }
    public function getCompanyFieldId(): ?string
    {
        return $this->companyFieldId;
    }


    public function toArray(): array
    {
        return array_filter([
            'name' => ["ar"=>$this->name],
            'country_id' => $this->countryId,
            'company_type_id' => $this->companyTypeId,
            'company_field_id' => $this->companyFieldId,
        ]);
    }
}
