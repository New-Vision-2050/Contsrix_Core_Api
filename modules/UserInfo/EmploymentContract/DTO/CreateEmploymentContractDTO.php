<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\DTO;

use Illuminate\Http\UploadedFile;

class CreateEmploymentContractDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,

        public ?string $contract_number,
        public ?string $start_date,
        public ?string $commencement_date,
        public ?string $contract_duration,

        public ?string $notice_period,
        public ?string $probation_period,
        public ?string $nature_work_id,
        public ?string $type_working_hour_id,

        public ?string $working_hours,
        public ?string $annual_leave,
        public ?string $latitude,
        public ?string $longitude,
        public ?string $right_terminate_id,

        public ?string $contract_duration_unit,
        public ?string $notice_period_unit,
        public ?string $probation_period_unit,

        public ?string $leave_category_id = null,
        public bool $leave_category_provided = false,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,

            'contract_number' => $this->contract_number,
            'start_date' => $this->start_date,
            'commencement_date' => $this->commencement_date,
            'contract_duration' => $this->contract_duration,

            'notice_period' => $this->notice_period,
            'probation_period' => $this->probation_period,
            'nature_work_id' => $this->nature_work_id,
            'type_working_hour_id' => $this->type_working_hour_id,

            'working_hours' => $this->working_hours,
            'annual_leave' => $this->annual_leave,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'right_terminate_id' => $this->right_terminate_id,

            'contract_duration_unit' => $this->contract_duration_unit,
            'notice_period_unit' => $this->notice_period_unit,
            'probation_period_unit' => $this->probation_period_unit,
        ];

        if ($this->leave_category_provided) {
            $data['leave_category_id'] = $this->leave_category_id;
        }

        return $data;
    }
}
