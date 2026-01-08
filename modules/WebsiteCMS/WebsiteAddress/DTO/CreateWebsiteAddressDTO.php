<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateWebsiteAddressDTO
{
    public function __construct(
        public readonly array $title,
        public readonly ?string $address = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly int $status = 1,
    ) {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
        ];
    }
}
