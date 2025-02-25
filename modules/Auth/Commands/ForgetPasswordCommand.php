<?php

namespace Modules\Auth\Commands;

use Ramsey\Uuid\UuidInterface;

class ForgetPasswordCommand
{
    public function __construct(

        private string $identifier,
    ) {
    }


    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }


}
