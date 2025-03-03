<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class LoginStepAlternativeCommand
{
    public function __construct(

        private string $loginOption,
        private string $token,
        private string $identifier,
    ) {
    }


    public function getPassword(): ?string
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
