<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateBranchDTO
{
    public function __construct(
        public string $name,
        public UuidInterface $companyId,
        public UuidInterface $parentId,
        public string $phone,
        public string $phoneCode,
        public string $email,
        public string $lattitude,
        public string $longitude,
        public string $countryId,
        public string $stateId,
        public string $cityId,

    ) {
    }

    public function branchToArray(): array
    {
        return [
            'name' => $this->name,
            'company_id' => $this->companyId,
            'parent_id' => $this->parentId,
            'phone' => $this->phone,
            'phone_code' => $this->phoneCode,
            'email' => $this->email,
            'lattitude' => $this->lattitude,
            'longitude' => $this->longitude,

            "type" => "branch"

        ];
    }

    public function AddressToArray()
    {
        return [
          "country_id" => $this->countryId,
          "state_id" => $this->stateId,
          "city_id" => $this->cityId
        ];
    }
}
