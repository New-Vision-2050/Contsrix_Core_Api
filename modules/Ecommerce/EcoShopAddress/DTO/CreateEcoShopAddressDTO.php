<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoShopAddressDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $countryId,
        public string $cityId,
        public string $timeZoneId,
        public ?string $district = null,
        public ?string $street = null,
        public ?string $buildingNumber = null,
        public ?string $postalCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'country_id' => $this->countryId,
            'city_id' => $this->cityId,
            'time_zone_id' => $this->timeZoneId,
            'district' => $this->district,
            'street' => $this->street,
            'building_number' => $this->buildingNumber,
            'postal_code' => $this->postalCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    public function getCompanyId(): UuidInterface
    {
        return $this->companyId;
    }
}
