<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class CreateEcoBrandDashboardDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $name,
        public ?string $description
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'description' => $this->description
        ];
    }
}
