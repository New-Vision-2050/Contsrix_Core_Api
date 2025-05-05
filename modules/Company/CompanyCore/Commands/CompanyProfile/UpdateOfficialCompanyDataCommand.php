<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class UpdateOfficialCompanyDataCommand
{
    public function __construct(
        private UuidInterface $id,
        private string        $nameEn,
        private string        $email,
        private string        $phone,
        private string        $branchName,
        private string        $companyTypeId
    )
    {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getNameEn(): ?string
    {
        return $this->nameEn;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getBranchName()
    {
        return $this->branchName;
    }

    public function toArray(): array
    {
        return [
            'name' => ["en" => $this->nameEn],
            'email' => $this->email,
            'phone' => $this->phone,
            'company_type_id' => $this->companyTypeId,

        ];
    }
}
