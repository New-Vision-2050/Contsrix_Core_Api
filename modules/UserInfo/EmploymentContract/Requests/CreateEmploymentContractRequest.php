<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\UserInfo\EmploymentContract\DTO\CreateEmploymentContractDTO;

class CreateEmploymentContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|string',

            'contract_number' => 'nullable|string',
            'start_date' => 'nullable|string',
            'commencement_date' => 'nullable|string',
            'contract_duration' => 'nullable|string',
            'contract_duration_unit' => 'nullable|string',

            'notice_period' => 'nullable|numeric',
            'notice_period_unit' => 'nullable|string',
            'probation_period' => 'nullable|numeric',
            'probation_period_unit' => 'nullable|string',

            'nature_work_id' => 'nullable|string',
            'type_working_hour_id' => 'nullable|string',

            'working_hours' => 'nullable|numeric',
            'annual_leave' => 'nullable|numeric',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'right_terminate_id' => 'nullable|string',

            'file' => 'nullable|array',
            'file.*' => 'nullable',
        ];
    }

    public function createCreateEmploymentContractDTO(): CreateEmploymentContractDTO
    {
        return new CreateEmploymentContractDTO(
            company_id: '',
            global_id: '',

            contract_number: $this->get('contract_number'),
            start_date: $this->get('start_date'),
            commencement_date: $this->get('commencement_date'),
            contract_duration: $this->get('contract_duration'),

            notice_period: $this->get('notice_period'),
            probation_period: $this->get('probation_period'),
            nature_work_id: $this->get('nature_work_id'),
            type_working_hour_id: $this->get('type_working_hour_id'),

            working_hours: $this->get('working_hours'),
            annual_leave: $this->get('annual_leave'),
            latitude: $this->get('latitude'),
            longitude: $this->get('longitude'),
            right_terminate_id: $this->get('right_terminate_id'),

            contract_duration_unit: $this->get('contract_duration_unit'),
            notice_period_unit: $this->get('notice_period_unit'),
            probation_period_unit: $this->get('probation_period_unit'),
        );
    }
}
