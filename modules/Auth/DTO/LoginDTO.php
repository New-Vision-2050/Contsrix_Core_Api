<?php

namespace Modules\Auth\DTO;

class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public int $continue_with_otp=0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }

    public function getEmail()
    {
        return $this->email;
    }
    public function getContinueWithOtp()
    {
        return $this->continue_with_otp;
    }

}
