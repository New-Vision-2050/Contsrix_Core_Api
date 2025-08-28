<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoComplaintDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $ecoClientId,
        public string $name,
        public string $status = 'pending',
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'eco_client_id' => $this->ecoClientId,
            'name' => $this->name,
            'status' => $this->status,
        ];
    }
}
