<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateIdentityDataCommand
{
    public function __construct(
        public ? string $passport,
        public ? string $identity,
        public ? string $border_number,
        public ? string $entry_number,
    ) {
    }

    public function toArray(): array
    {
        return [
            'passport' => $this->passport,
            'identity' => $this->identity,
            'border_number' => $this->border_number,
            'entry_number' => $this->entry_number,
        ];
    }
}
