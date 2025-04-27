<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserSalary\DTO\CreateUserSalaryDTO;

class CreateUserSalaryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id'=> 'required|string',
            'hour_rate'=> 'nullable',
            'salary'=> 'required|string',
            'type'=> 'required|string',
            'description'=> 'nullable|string',
            'salary_type_code'=> 'required|string',
        ];
    }

    public function createCreateUserSalaryDTO(): CreateUserSalaryDTO
    {
        return new CreateUserSalaryDTO(
            company_id: '',
            global_id: '',
            hour_rate: $this->get('hour_rate'),
            salary: $this->get('salary'),
            type: $this->get('type'),
            description: $this->get('description'),
            salary_type_code: $this->get('salary_type_code'),
        );
    }
}
