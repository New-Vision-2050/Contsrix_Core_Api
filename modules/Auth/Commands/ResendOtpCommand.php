<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class ResendOtpCommand
{
    public function __construct(

        private string $email,
    ) {
    }






    public function getEmail(): ?string

    {
        return $this->email;
    }


}
