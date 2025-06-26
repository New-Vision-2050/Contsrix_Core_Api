<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateBusinessTypeDTO
{
    public function __construct(
        public string $name,
        public string $description
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description'=> $this->description
        ];
    }
}
