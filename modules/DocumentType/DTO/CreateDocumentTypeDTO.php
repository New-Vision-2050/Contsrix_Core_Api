<?php

declare(strict_types=1);

namespace Modules\DocumentType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateDocumentTypeDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $is_active = 1,
        public readonly ?string $company_id = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_active' => $this->is_active,
            'company_id' => $this->company_id,
        ];
    }
}
