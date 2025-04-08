<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEmailOtpCommand
{
    public function __construct(
        public string $email,
    ) {
    }


    public function toArray(): array
    {
        return [
            'email' => $this->email,
        ];
    }
}
