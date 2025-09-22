<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Commands\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoAddressDashboardCommand
{
    public function __construct(
        private UuidInterface $id,
         private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $email = null,
        private ?string $phoneCode = null,
        private ?string $phone = null,
        private ?string $countryId = null,
        private ?string $cityId = null,
        private ?string $stateId = null,
        private ?string $address = null,
        private ?string $address2 = null,
        private ?string $zipCode = null,
        private ?string $type = null,
        private ?bool $isDefault = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
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
