<?php

namespace Modules\Auth\DTO;

use Ramsey\Uuid\UuidInterface;

class ValidateOtpDTO
{
    public function __construct(

        private string $otp,
        private string $identifier,
    ) {
    }




    public function getOtp(): ?string

    {
        return $this->otp;
    }

    public function getIdentifier(): ?string

    {
        return $this->identifier;
    }


}
