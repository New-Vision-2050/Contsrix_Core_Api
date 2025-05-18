<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO\Broker;


class CreateBrokerDTO
{
    public function __construct(
        public string  $name,
        public string  $email,
        private ?string        $countryId,
        public string  $phone,

        public ?string $residence,
        public ?array $branchIds

    )
    {
    }

    public function getCoutryId()
    {
        return $this->countryId;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'country_id' => $this->countryId,
            'phone' => $this->phone,
            'residence' => $this->residence,
        ];
    }


    public function getBranchIds()
    {
        return $this->branchIds;
    }



}
