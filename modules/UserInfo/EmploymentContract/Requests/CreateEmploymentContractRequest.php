<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\EmploymentContract\DTO\CreateEmploymentContractDTO;

class CreateEmploymentContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|string',

            'contract_number' => 'required|string',
            'start_date' => 'required|string',
            'commencement_date' => 'required|string',
            'contract_duration' => 'required|string',

            'notice_period' => 'required|numeric',
            'notice_period_unit' => 'required|string',
            'probation_period' => 'required|numeric',
            'probation_period_unit' => 'required|string',

            'nature_work' => 'required|string',
            'type_working_hours' => 'required|string',

            'working_hours' => 'required|numeric',
            'annual_leave' => 'required|numeric',
            'country_id' => 'required|string',
            'right_terminate' => 'required|string',
            'right_terminate_unit' => 'required|string',
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
            nature_work: $this->get('nature_work'),
            type_working_hours: $this->get('type_working_hours'),

            working_hours: $this->get('working_hours'),
            annual_leave: $this->get('annual_leave'),
            country_id: $this->get('country_id'),
            right_terminate: $this->get('right_terminate'),
            file: $this->file('file'),

            contract_duration_unit: $this->get('contract_duration_unit'),
            notice_period_unit: $this->get('notice_period_unit'),
            right_terminate_unit: $this->get('right_terminate_unit'),

        );
    }
}
