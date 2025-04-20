<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class GeoCodingDTO
{
    public function __construct(
        private string $latitude,
        private string $longitude,
        private $branch
    ) {
    }




    public function getLatitude()
    {
        return $this->latitude;
    }

    public function getBranch()
    {
        return $this->branch;
    }


    public function getLongitude()
    {
        return $this->longitude;
    }

    public function toArray(): array
    {
        return array_filter([
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
        ]);
    }
}
