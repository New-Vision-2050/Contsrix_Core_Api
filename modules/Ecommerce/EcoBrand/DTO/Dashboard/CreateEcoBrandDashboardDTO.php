<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class CreateEcoBrandDashboardDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public array $name, // Changed to array for multilingual support
        public ?array $description = null // Changed to array for multilingual support
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'name' => $this->name,
            'description' => $this->description
        ];
    }
}
