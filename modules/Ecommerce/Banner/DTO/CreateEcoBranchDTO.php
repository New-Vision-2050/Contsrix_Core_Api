<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoBranchDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public ?UuidInterface $settingPageId,
        public string $name,
        public string $country,
        public string $address,
        public string $phone,
        public string $email,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $isActive = true,
        public bool $isMainBranch = false,
        public int $displayOrder = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'setting_page_id' => $this->settingPageId?->toString(),
            'name' => $this->name,
            'country' => $this->country,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->isActive,
            'is_main_branch' => $this->isMainBranch,
            'display_order' => $this->displayOrder,
        ];
    }
}
