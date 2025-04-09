<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEmailOtpCommand
{
    public function __construct(
        public string $identifier,
        public string $type,
    ) {
    }


    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'type' => $this->type
        ];
    }
}
