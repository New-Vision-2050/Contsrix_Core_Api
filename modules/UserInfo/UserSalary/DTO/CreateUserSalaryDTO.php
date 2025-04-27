<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserSalaryDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public string $hour_rate,
        public string $salary,
        public string $period_id,
        public string $description,
        public string $salary_type_code
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'hour_rate' => $this->hour_rate,
            'salary' => $this->salary,
            'period_id' => $this->period_id,
            'description' => $this->description,
            'salary_type_code'=> $this->salary_type_code
        ];
    }
}
