<?php

namespace Modules\Auth\DTO;

use Ramsey\Uuid\UuidInterface;

class LoginStepAlternativeDTO
{
    public function __construct(

        private string $loginOption,
        private string $token,
        private string $identifier,
    ) {
    }


    public function getLoginOption(): ?string
    {
        return $this->loginOption;
    }

    public function getToken(): ?string

    {
        return $this->token;
    }

    public function getIdentifier(): ?string

    {
        return $this->identifier;
    }


}
