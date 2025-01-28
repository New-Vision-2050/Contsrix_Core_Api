<?php

namespace Modules\Auth\DTO;

class LoginWithOtpDTO
{
    public function __construct(
        public string $email,
        public string $otp,
    ) {
    }



    public function getEmail()
    {
        return $this->email;
    }
    public function getOtp()
    {
        return $this->otp;
    }

}
