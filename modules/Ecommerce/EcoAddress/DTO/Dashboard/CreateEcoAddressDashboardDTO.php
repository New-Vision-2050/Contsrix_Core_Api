<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class CreateEcoAddressDashboardDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $ecoClientId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneCode,
        public string $phone,
        public string $countryId,
        public string $cityId,
        public string $stateId,
        public string $address,
        public ?string $zipCode = null,
        public string $type = 'shipping',
        public bool $isDefault = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'eco_client_id' => $this->ecoClientId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone_code' => $this->phoneCode,
            'phone' => $this->phone,
            'country_id' => $this->countryId,
            'city_id' => $this->cityId,
            'state_id' => $this->stateId,
            'address' => $this->address,
            'zip_code' => $this->zipCode,
            'type' => $this->type,
            'is_default' => $this->isDefault,
        ];
    }
}
