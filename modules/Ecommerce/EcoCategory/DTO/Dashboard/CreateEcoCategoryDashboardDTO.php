<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class CreateEcoCategoryDashboardDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public array $name, // Changed to array for multilingual support
        public ?UuidInterface $parentId = null, // Fixed typo and changed to UuidInterface
        public int $priority = 0
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'name' => $this->name, // Array with 'ar' and 'en' keys
            'parent_id' => $this->parentId?->toString(),
            'priority' => $this->priority,
        ];
    }
}
