<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class SetCompanyAddressCommand
{
    public function __construct(
        private UuidInterface $id,
        private string        $countryId,
        private string        $stateId,
        private string        $cityId,
        private string        $neighborhoodName,
        private string        $streetName,
        private string        $buildingNumber,
        private string        $addtionalPhone,
        private string        $postalCode,
    )
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            "country_id" => $this->countryId,
            "state_id" => $this->stateId,
            "city_id" => $this->cityId,
            "neighborhood_name" => $this->neighborhoodName,
            "street_name" => $this->streetName,
            "building_number" => $this->buildingNumber,
            "additional_phone" => $this->addtionalPhone,
            "postal_code" => $this->postalCode,

        ];
    }
}
