<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class AddressPresenter extends AbstractPresenter
{
    private  $address;

    /**
     * Constructor to initialize with an address array or object
     *
     * @param array|object $address The address data
     */
    public function __construct($address)
    {
        $this->address = $address;
    }

    /**
     * Present address data
     *
     * @param bool $isListing
     * @return array
     */
    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->address['id'] ?? null,
            'company_id' => $this->address['company_id'] ?? null,
            'country_id' => $this->address['country_id'] ?? null,
            'city_id' => $this->address['city_id'] ?? null,
            'state_id' => $this->address['state_id'] ?? null,
            'neighborhood_name' => $this->address['neighborhood_name'] ?? null,
            'street_name' => $this->address['street_name'] ?? null,
            'building_number' => $this->address['building_number'] ?? null,
            'additional_phone' => $this->address['additional_phone'] ?? null,
            'postal_code' => $this->address['postal_code'] ?? null,
            'management_hierarchy_id' => $this->address['management_hierarchy_id'] ?? null,
            'country_name' => $this->address['country_name'] ?? null,
            'state_name' => $this->address['state_name'] ?? null,
            'city_name' => $this->address['city_name'] ?? null,
            'country_lat' => $this->address['country_lat'] ?? null,
            'country_long' => $this->address['country_long'] ?? null,
            'country_iso2' => $this->address['country_iso2'] ?? null,
            'created_at' => $this->address['created_at'] ?? null,
            'updated_at' => $this->address['updated_at'] ?? null,
        ];
    }


}
