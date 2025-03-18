<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateFileDTO
{
    public function __construct(
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
