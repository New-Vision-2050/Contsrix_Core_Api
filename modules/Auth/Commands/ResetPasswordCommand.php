<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class ResetPasswordCommand
{
    public function __construct(

        private string $otp,
        private string $password,
        private string $email,
    ) {
    }


    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getOtp(): ?string

    {
        return $this->otp;
    }

    public function getEmail(): ?string

    {
        return $this->email;
    }


}
