<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateJobTypeWithCompanyDTO
{
    public function __construct(
        public string $name,
        public UuidInterface $companyId,
        public int $status
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => ["ar"=>$this->name,"en"=>$this->name],
            'company_id' => $this->companyId,
            'status' => $this->status
        ];
    }
}
