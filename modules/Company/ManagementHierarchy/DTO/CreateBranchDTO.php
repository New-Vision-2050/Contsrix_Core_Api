<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateBranchDTO
{
    public function __construct(
        public string $name,
        public UuidInterface $companyId,
        public ?int $parentId,
        public UuidInterface $managerId,
        public string $phone,
        public string $email,
        public ?string $latitude,
        public ?string $longitude,
        public string $countryId,
        public string $stateId,
        public string $cityId,

    ) {
    }

    public function branchToArray(): array
    {
        $phone =getPhoneNumberInfo($this->phone);
        return array_merge([
            'name' => $this->name,
            'company_id' => $this->companyId,
            'parent_id' => $this->parentId,

            'email' => $this->email,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            "manager_id" => $this->managerId,

            "type" => "branch"

        ],$phone);
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
