<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateJobTypeDTO
{
    public function __construct(
        public string $name,
//        public string $company_id,
        public bool $status = true
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'company_id' => tenant("id"),
            'status' => $this->status
        ];
    }
}
