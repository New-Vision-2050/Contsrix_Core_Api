<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateProjectTypeDTO
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
