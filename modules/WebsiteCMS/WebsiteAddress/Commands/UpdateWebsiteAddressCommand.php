<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteAddressCommand
{
    public function __construct(
        private UuidInterface $id,
        private int $cityId,
        private array $title,
        private ?float $latitude = null,
        private ?float $longitude = null,
        private int $status = 1,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getCityId(): int
    {
        return $this->cityId;
    }

    public function getTitle(): array
    {
        return $this->title;
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
            'city_id' => $this->cityId,
            'title' => $this->title,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
        ];
    }
}
