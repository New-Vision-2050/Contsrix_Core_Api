<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUserProfessionalDataCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $branch_id,
        private string $management_id,
        private string $department_id,
        private string $job_type_id,
        private string $job_title_id,
        private string $job_code,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'branch_id' => $this->branch_id,
            'management_id' => $this->management_id,
            'department_id' => $this->department_id,
            'job_type_id' => $this->job_type_id,
            'job_title_id' => $this->job_title_id,
            'job_code' => $this->job_code,
        ]);
    }
}
