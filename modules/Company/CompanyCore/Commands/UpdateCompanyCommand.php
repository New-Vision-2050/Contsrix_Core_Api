<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $userName,
        private string $email,
        private string $phone,
        private string $countryId,
        private string $companyTypeId,
        private string $companyFieldId,
        private string $registrationTypeId,
        private string $registrationNo,
        private string $serialNo,
        private string $generalManagerId,
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
    public function getUserName(): ?string
    {
        return $this->userName;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function getPhone(): ?string
    {
        return $this->phone;
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
    public function getRegistrationTypeId(): ?string
    {
        return $this->registrationTypeId;
    }
    public function getRegistrationNo(): string
    {
        return $this->registrationNo;
    }

    public function getSerialNo(): string
    {
        return $this->serialNo;
    }


    public function getGeneralManagerId(): ?string
    {
        return $this->generalManagerId;
    }
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'user_name' => $this->userName,
            'email' => $this->email,
            'serial_no' => $this->serialNo,
            'phone' => $this->phone,
            'country_id' => $this->countryId,
            'company_type_id' => $this->companyTypeId,
            'company_field_id' => $this->companyFieldId,
            'registration_type_id' => $this->registrationTypeId,
            'registration_no' => $this->registrationNo,
            'general_manager_id' => $this->generalManagerId
        ]);
    }
}
