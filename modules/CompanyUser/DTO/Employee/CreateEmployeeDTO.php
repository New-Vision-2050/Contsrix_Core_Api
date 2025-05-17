<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO\Employee;


class CreateEmployeeDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ? string $countryId,
        public string $phone,
        public string $jobTitleId,
        public int $status,
        public int $branchId

    ) {
    }

    public function getCoutryId()
    {
        return $this->countryId;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->firstName.' '.$this->lastName,
            'email' => $this->email,
            'country_id' => $this->countryId,
            'job_title_id'=>$this->jobTitleId,
            'phone' => $this->phone,
            "status"=>$this->status

        ];
    }

    public function getBranchId()
    {
        return [$this->branchId];
    }

}
