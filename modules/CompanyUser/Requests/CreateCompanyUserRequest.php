<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\Rules\CompanyUserValidation;
use Modules\CompanyUser\Rules\UserNameValidation;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;

class CreateCompanyUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required','string',new UserNameValidation()],
            'role' => 'nullable',
            'company_id' => 'required|exists:companies,id',
            'country_id' => 'nullable|exists:countries,id',
            'phone' => 'required|phone|unique:company_users,phone',
            'email' => 'required|email|unique:company_users,email',
            'job_title_id'=>'required|exists:job_titles,id',
            'border_number' => 'nullable|unique:company_users,border_number',
            'residence' => 'nullable|unique:company_users,residence',
            'passport' => 'nullable|unique:company_users,passport',
            'identity' => 'nullable|unique:company_users,identity',
            'company_user_validation' => [new CompanyUserValidation($this->get('company_id'), $this->get('country_id'))],


        ];
    }

    public function createCreateCompanyUserDTO(): CreateCompanyUserDTO
    {
        return new CreateCompanyUserDTO(
            name: $this->get('name'),
            email: $this->get('email'),
            country_id: $this->get('country_id'),
            phone: $this->get('phone'),
            border_number: $this->get('border_number'),
            residence: $this->get('residence'),
            identity: $this->get('identity'),
            passport: $this->get('passport'),
            job_title_id: $this->get('job_title_id'),
        );
    }

    public function createCreateCompanyUserCompanyRoleDTO(): CreateCompanyUserCompanyRoleDTO
    {
        return new CreateCompanyUserCompanyRoleDTO(
            company_id: Uuid::fromString($this->get('company_id')),
            role: $this->get('role')?? '1',

        );
    }
}
