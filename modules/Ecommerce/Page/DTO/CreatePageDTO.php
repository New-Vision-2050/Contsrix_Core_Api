<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\DTO;

use Ramsey\Uuid\UuidInterface;

class CreatePageDTO
{
    public function __construct(
        public array $description,
        public string $type,
        public string $companyId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'type' => $this->type,
            'company_id' => $this->companyId,
        ];
    }
}
