<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\DTO;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class CreateEmploymentContractDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,

        public string $contract_number,
        public string $start_date,
        public string $commencement_date,
        public string $contract_duration,

        public string $notice_period,
        public string $probation_period,
        public string $nature_work,
        public string $type_working_hours,

        public string $working_hours,
        public string $annual_leave,
        public string $country_id,
        public string $right_terminate,
        public ? UploadedFile $file,

        public string $contract_duration_unit,
        public string $notice_period_unit,
        public string $probation_period_unit,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,

            'contract_number' => $this->contract_number,
            'start_date' => $this->start_date,
            'commencement_date' => $this->commencement_date,
            'contract_duration' => $this->contract_duration,

            'notice_period' => $this->notice_period,
            'probation_period' => $this->probation_period,
            'nature_work' => $this->nature_work,
            'type_working_hours' => $this->type_working_hours,

            'working_hours' => $this->working_hours,
            'annual_leave' => $this->annual_leave,
            'country_id' => $this->country_id,
            'right_terminate' => $this->right_terminate,
            'file' => $this->file?->getClientOriginalName(),

            'contract_duration_unit'=> $this->contract_duration_unit,
            'notice_period_unit'=> $this->notice_period_unit,
            'probation_period_unit'=> $this->probation_period_unit,
        ];
    }
}
