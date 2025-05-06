<?php

declare(strict_types=1);

namespace Modules\JobTitle\DTO;

use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

class CreateJobTitleDTO
{
    public function __construct(
        public string $name,
        public ?UuidInterface $job_type_id = null,
        public ?string $description = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'job_type_id' => $this->job_type_id,
            'description' => $this->description,
            'company_id' => tenant("id"),
            "status"=>1
        ];
    }
}
