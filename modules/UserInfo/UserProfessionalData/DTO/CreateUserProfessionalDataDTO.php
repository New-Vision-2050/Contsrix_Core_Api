<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserProfessionalDataDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public string $branch_id,
        public string $management_id,
        public string $department_id,
        public string $job_type_id,
        public string $job_title_id,
        public string $job_code,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'branch_id' => $this->branch_id,
            'management_id' => $this->management_id,
            'department_id' => $this->department_id,
            'job_type_id' => $this->job_type_id,
            'job_title_id' => $this->job_title_id,
            'job_code' => $this->job_code,
        ];
    }
}
