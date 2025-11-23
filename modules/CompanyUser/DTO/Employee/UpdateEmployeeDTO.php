<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO\Employee;


class UpdateEmployeeDTO
{
    public function __construct(

        public ?string $id,
        public ?string $jobTitleId,
        public int $status,
        public ?int $branchId

    ) {
    }


    public function getId()
    {
        return $this->id;
    }


    public function toArray(): array
    {
        return [

            'job_title_id'=>$this->jobTitleId,
            "status"=>$this->status,
            "branch_id"=>$this->branchId

        ];
    }

    public function getBranchId()
    {
        return $this->branchId != null?[$this->branchId]:null;
    }

}
