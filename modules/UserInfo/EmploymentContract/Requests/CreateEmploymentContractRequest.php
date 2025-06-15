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

<<<<<<< HEAD
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
            'state_id' => 'nullable|string',
            'right_terminate_id' => 'nullable|string',
=======
            'contract_number' => 'required|string',
            'start_date' => 'required|string',
            'commencement_date' => 'required|string',
            'contract_duration' => 'required|string',
            'contract_duration_unit' => 'required|string',

            'notice_period' => 'required|numeric',
            'notice_period_unit' => 'required|string',
            'probation_period' => 'required|numeric',
            'probation_period_unit' => 'required|string',

            'nature_work_id' => 'required|string',
            'type_working_hour_id' => 'required|string',

            'working_hours' => 'required|numeric',
            'annual_leave' => 'required|numeric',
            'country_id' => 'required|string',
            'right_terminate_id' => 'required|string',
>>>>>>> 7be6c72c (merge with stage (first version ))

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
<<<<<<< HEAD
            state_id: $this->get('state_id'),
=======
            country_id: $this->get('country_id'),
>>>>>>> 7be6c72c (merge with stage (first version ))
            right_terminate_id: $this->get('right_terminate_id'),

            contract_duration_unit: $this->get('contract_duration_unit'),
            notice_period_unit: $this->get('notice_period_unit'),
            probation_period_unit: $this->get('probation_period_unit'),
        );
    }
}
