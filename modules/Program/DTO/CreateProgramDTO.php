<?php

declare(strict_types=1);

namespace Modules\Program\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateProgramDTO
{
    public function __construct(
        public array $name,
        public ?string $parentId
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->parentId,
        ];
    }
}
