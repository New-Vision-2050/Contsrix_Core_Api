<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserProfessionalDataDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public string $user_id,
        public string $branch_id,
        public string $management_id,
        public string $job_type_id,
        public string $job_title_id,
        public string $job_code,
        public ?string $attendance_constraint_id
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'management_id' => $this->management_id,
            'job_type_id' => $this->job_type_id,
            'job_title_id' => $this->job_title_id,
            'job_code' => $this->job_code,
            'attendance_constraint_id' => $this->attendance_constraint_id
        ];
    }
}
