<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateBranchCommand
{
    public function __construct(
        private UuidInterface $id,
        public string $name,
        public UuidInterface $companyId,
        public ?UuidInterface $parentId,
        public UuidInterface $managerId,
        public string $phone,
        public string $email,
        public string $latitude,
        public string $longitude,
        public string $countryId,
        public string $stateId,
        public string $cityId,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function toArray(): array
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

    public function addressToArray()
    {
        return [
            "country_id" => $this->countryId,
            "state_id" => $this->stateId,
            "city_id" => $this->cityId
        ];
    }
}
