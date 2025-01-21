<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class ForgetPasswordCommand
{
    public function __construct(

        private string $email,
    ) {
    }


    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
        ]);
    }
}
