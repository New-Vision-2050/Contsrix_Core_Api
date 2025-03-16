<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateManagementHierarchyDTO
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
