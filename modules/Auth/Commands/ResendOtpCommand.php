<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class ResendOtpCommand
{
    public function __construct(

        private string $otp,
        private string $email,
    ) {
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
