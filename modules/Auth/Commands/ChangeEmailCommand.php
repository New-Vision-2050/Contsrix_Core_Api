<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class ChangeEmailCommand
{
    public function __construct(
        private $token ,
        private string $email,
        private string $newEmail,
    )
    {
    }


    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getNewEmail(): ?string
    {
        return $this->newEmail;
    }

    public function getToken()
    {
        return $this->token;
    }


}
