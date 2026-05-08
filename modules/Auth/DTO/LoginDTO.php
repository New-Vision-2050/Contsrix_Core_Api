<?php

namespace Modules\Auth\DTO;

class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
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

    public function getPassword()
    {
        return $this->password;
    }
}
