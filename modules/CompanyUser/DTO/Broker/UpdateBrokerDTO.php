<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO\Broker;


class UpdateBrokerDTO
{
    public function __construct(
        private $id,


        public ?array $branchIds,
        public ?string $messageAddress,
        public ?string $registrationNumber,
        public ?string $companyRepresentativeName,
        public ?string $companyName


    )
    {
    }







    public function getBranchIds()
    {
        return $this->branchIds;
    }

    public function toArray()
    {
        return ["message_address"=>$this->messageAddress];
    }

    public function brokerDetailToArray(): array
    {
        return [
            'registration_number' => $this->registrationNumber,
            'company_representative_name' => $this->companyRepresentativeName,
            'company_name' => $this->companyName,
        ];
    }

    public function getId()
    {
        return $this->id;
    }

}
