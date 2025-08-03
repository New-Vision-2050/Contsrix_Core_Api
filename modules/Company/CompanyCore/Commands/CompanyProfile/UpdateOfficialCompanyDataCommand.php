<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands\CompanyProfile;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Ramsey\Uuid\UuidInterface;

class UpdateOfficialCompanyDataCommand
{
    public function __construct(
        private UuidInterface       $id,
        private string              $nameEn,
        private string              $email,
        private string              $phone,
        private string              $branchName,
        private string              $companyTypeId,
        private ManagementHierarchy $branch,
        private array              $packages
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
        $data = [
            'name' => ["en" => $this->nameEn],
            'company_type_id' => $this->companyTypeId,

        ];
        if ($this->branch->is_main == 1) {
            $data += [
                "phone" => $this->phone,
                "email" => $this->email
            ];
        }

        return $data;
    }

    public function packages()
    {
        return $this->packages;
    }
}
