<?php

namespace Modules\Auth\DTO;

class CheckLoginDTO
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
}
