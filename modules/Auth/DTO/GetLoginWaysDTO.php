<?php

namespace Modules\Auth\DTO;

use Ramsey\Uuid\UuidInterface;

class GetLoginWaysDTO
{
    public function __construct(
        public string $identifier,
        public UuidInterface $companyId,
    ) {
    }


    public function getIdentifier()
    {
        return $this->identifier;
    }


    public function getCompanyId()
    {
        return $this->companyId;
    }
}
