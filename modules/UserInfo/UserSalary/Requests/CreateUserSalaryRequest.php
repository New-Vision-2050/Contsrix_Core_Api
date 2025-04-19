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
            'basic'=> 'required|string',
            'salary'=> 'required|string',
            'type'=> 'required|string',
            'description'=> 'required|string',
        ];
    }

    public function createCreateUserSalaryDTO(): CreateUserSalaryDTO
    {
        return new CreateUserSalaryDTO(
            company_id: '',
            global_id: '',
            basic: $this->get('basic'),
            salary: $this->get('salary'),
            type: $this->get('type'),
            description: $this->get('description'),
        );
    }
}
