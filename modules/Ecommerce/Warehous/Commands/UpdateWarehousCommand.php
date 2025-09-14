<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateWarehousCommand
{
    public function __construct(
        private UuidInterface $id,
        public ?string $name = null,
        public ?bool $isDefault = null,
        public ?string $countryId = null,
        public ?string $cityId = null,
        public ?string $district = null,
        public ?string $street = null,
        public ?float $latitude = null,
        public ?float $longitude = null,

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

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'is_default' => $this->isDefault,
            'country_id' => $this->countryId,
            'city_id' => $this->cityId,
            'district' => $this->district,
            'street' => $this->street,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ], fn ($value) => !is_null($value));
    }
}
