<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO\Client;


use Ramsey\Uuid\UuidInterface;

class CreateClientDTO
{
    public function __construct(
        public string         $name,
        public string         $email,
        private ?string       $countryId,
        public string         $phone,

        public ?string        $residence,
        public ?array         $branchIds,
        public ?UuidInterface $brokerId,
        public int            $type,
        public ?string        $registrationNumber,
        public ?string        $companyRepresentativeName,
        public ?string        $messageAddress


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
            'phone' => $this->phone,
            'residence' => $this->residence,
            "message_address" => $this->messageAddress,
        ];
    }

    public function clientDetailToArray(): array
    {
        return [
            'broker_id' => $this->brokerId,
            'type' => $this->type,
            'registration_number' => $this->registrationNumber,
            'company_representative_name' => $this->companyRepresentativeName,
        ];
    }


    public function getBranchIds()
    {
        return $this->branchIds;
    }


}
