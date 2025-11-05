<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateStoreBranchDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $type,
        public string $name,
        public ?string $countryId = null,
        public ?string $address = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'type' => $this->type,
            'name' => $this->name,
            'country_id' => $this->countryId,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->isActive,
        ];
    }
}
