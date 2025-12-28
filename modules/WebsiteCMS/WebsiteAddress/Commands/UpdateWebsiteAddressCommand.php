<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteAddressCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $title,
        private ?string $address = null,
        private ?float $latitude = null,
        private ?float $longitude = null,
        private int $status = 1,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }



    public function getTitle(): array
    {
        return $this->title;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getStatus(): int
    {
        return $this->status;
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
