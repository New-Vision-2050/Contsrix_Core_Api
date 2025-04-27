<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserSalaryDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public string $basic,
        public string $salary,
        public string $type,
        public string $description,
        public string $salary_type_code
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'basic' => $this->basic,
            'salary' => $this->salary,
            'type' => $this->type,
            'description' => $this->description,
            'salary_type_code'=> $this->salary_type_code
        ];
    }
}
