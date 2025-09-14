<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateWarehousDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $name,
        public bool $isDefault,
        public string $countryId,
        public string $cityId,
        public ?string $district = null,
        public string $street,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'is_default' => $this->isDefault,
            'country_id' => $this->countryId,
            'city_id' => $this->cityId,
            'district' => $this->district,
            'street' => $this->street,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
