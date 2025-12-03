<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO\Client;


use Ramsey\Uuid\UuidInterface;

class UpdateClientDTO
{
    public function __construct(

        private $id,

        private ?string       $countryId,

        public ?array         $branchIds,
        public ?UuidInterface $brokerId,
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

            "message_address" => $this->messageAddress,
        ];
    }

    public function clientDetailToArray(): array
    {
        return [
            'broker_id' => $this->brokerId,
            'registration_number' => $this->registrationNumber,
            'company_representative_name' => $this->companyRepresentativeName,
        ];
    }


    public function getBranchIds()
    {
        return $this->branchIds;
    }

    public function getId()
    {
        return $this->id;
    }


}
