<?php

namespace Modules\Auth\DTO;

use Ramsey\Uuid\UuidInterface;

class GetLoginWaysDTO
{
    public function __construct(
        public string $identifier,
    ) {
    }


    public function getIdentifier()
    {
        return $this->identifier;
    }

}
