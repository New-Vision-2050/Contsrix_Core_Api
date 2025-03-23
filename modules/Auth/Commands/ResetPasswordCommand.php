<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class ResetPasswordCommand
{
    public function __construct(

        private ?string $token,
        private string $password,
        private string $identifier,
    ) {
    }


    public function getPassword(): ?string
    {
        return $this->password;
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
