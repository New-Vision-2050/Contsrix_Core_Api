<?php

namespace Modules\Auth\DTO;

class LoginStepDTO
{
    public function __construct(
        public string $identifier,
        public string $password,
    ) {
    }

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'password' => $this->password,
        ];
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }


}
