<?php

namespace Modules\Auth\DTO;

class LoginStepDTO
{
    public function __construct(
        public string $identifier,
        public string $password,
        public string $token,
        public string $companyId
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


    public function getPassword()
    {
        return $this->password;
    }

    public function getToken()
    {
        return $this->token;
    }


    public function getCompanyId()
    {
        return $this->companyId;
    }


}
