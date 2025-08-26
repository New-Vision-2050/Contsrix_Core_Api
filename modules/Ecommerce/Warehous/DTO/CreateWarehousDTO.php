<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateWarehousDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'name' => $this->name,
        ];
    }
}
