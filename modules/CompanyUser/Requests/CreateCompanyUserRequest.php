<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;

class CreateCompanyUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'role' => 'required|array',
            'company_id' => 'required|exists:companies,id',
            'country_id' => 'required|exists:countries,id',
            'phone' => 'required|unique:company_users,phone',
            'email' => 'required|email|unique:company_users,phone',
        ];
    }

    public function createCreateCompanyUserDTO(): CreateCompanyUserDTO
    {
        return new CreateCompanyUserDTO(
            name: $this->get('name'),
        );
    }
}
