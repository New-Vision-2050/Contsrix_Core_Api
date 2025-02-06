<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $email,
        private string $phone,
        private string $country_id,
        private string $company_type_id,
        private string $company_field_id,
        private string $registration_type_id,
        private string $registration_no,
        private string $classification_no,
        private string $general_manager_id,
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
        return $this->country_id;
    }
    public function getCompanyTypeId(): ?string
    {
        return $this->company_type_id;
    }
    public function getCompanyFieldId(): ?string
    {
        return $this->company_field_id;
    }
    public function getRegistrationTypeId(): ?string
    {
        return $this->registration_type_id;
    }
    public function getRegistrationNo(): string
    {
        return $this->registration_no;
    }
    public function getClassificationNo(): string
    {
        return $this->classification_no;
    }


    public function getGeneralManagerId(): ?string
    {
        return $this->general_manager_id;
    }
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_id' => $this->country_id,
            'company_type_id' => $this->company_type_id,
            'company_field_id' => $this->company_field_id,
            'registration_type_id' => $this->registration_type_id,
            'registration_no' => $this->registration_no,
            'classification_no' => $this->classification_no,
            'general_manager_id' => $this->general_manager_id
        ]);
    }
}
