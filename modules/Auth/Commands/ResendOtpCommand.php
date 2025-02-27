<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class ResendOtpCommand
{
    public function __construct(

        private string $identifier,
        private string $token
    ) {
    }


    public function getIdentifier(): ?string

    {
        return $this->identifier;
    }

    public function getToken(): ?string

    {
        return $this->token;
    }


}
