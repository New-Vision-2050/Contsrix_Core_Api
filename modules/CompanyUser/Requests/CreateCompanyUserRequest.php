<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Rules\CompanyUserValidation;
use Modules\CompanyUser\Rules\PhoneEmailConsistencyRule;
use Modules\CompanyUser\Rules\UserNameValidation;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;

class CreateCompanyUserRequest extends FormRequest
{


    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', new UserNameValidation()],
            'last_name' => ['required', 'string', new UserNameValidation()],
            'role' => 'nullable',
            'company_id' => 'required|exists:companies,id',
            'country_id' => 'nullable|exists:countries,id',
            'time_zone_id' => 'nullable|exists:time_zones,id',
            'language_id' => 'nullable|exists:languages,id',
            'currency_id' => 'nullable|exists:countries,id',

            'phone' => [
                'required'
                , new PhoneEmailConsistencyRule($this->input('email'))
            ],
            'email' => [
                'required',
                'email'
            ],
            'job_title_id' => 'required|exists:job_titles,id',
            'border_number' => 'nullable|unique:company_users,border_number',
            'residence' => 'nullable|unique:company_users,residence',
            'passport' => 'nullable|unique:company_users,passport',
            'identity' => 'nullable|unique:company_users,identity',
            'company_user_validation' => [new CompanyUserValidation($this->get('company_id'), $this->get('country_id'))],


        ];
    }
    public function messages(): array
    {
        return [
            'first_name.required' => __('validation.company_user.first_name_required'),
            'last_name.required' => __('validation.company_user.last_name_required'),
            'company_id.required' => __('validation.company_user.company_id_required'),
            'company_id.exists' => __('validation.company_user.company_id_exists'),
            'country_id.exists' => __('validation.company_user.country_id_exists'),
            'time_zone_id.exists' => __('validation.company_user.time_zone_id_exists'),
            'language_id.exists' => __('validation.company_user.language_id_exists'),
            'currency_id.exists' => __('validation.company_user.currency_id_exists'),
            'phone.required' => __('validation.company_user.phone_required'),
            'email.required' => __('validation.company_user.email_required'),
            'email.email' => __('validation.company_user.email_invalid'),
            'job_title_id.required' => __('validation.company_user.job_title_required'),
            'job_title_id.exists' => __('validation.company_user.job_title_exists'),
            'border_number.unique' => __('validation.company_user.border_number_unique'),
            'residence.unique' => __('validation.company_user.residence_unique'),
            'passport.unique' => __('validation.company_user.passport_unique'),
            'identity.unique' => __('validation.company_user.identity_unique'),
        ];
    }
    public function createCreateCompanyUserDTO(): CreateCompanyUserDTO
    {
        return new CreateCompanyUserDTO(
            firstName: $this->get('first_name'),
            lastName: $this->get('last_name'),
            email: $this->get('email'),
            country_id: $this->get('country_id'),
            phone: $this->get('phone'),
            job_title_id: $this->get('job_title_id'),
            border_number: $this->get('border_number'),
            residence: $this->get('residence'),
            identity: $this->get('identity'),
            passport: $this->get('passport'),
            time_zone_id: $this->get('time_zone_id'),
            language_id: $this->get('language_id'),
            currency_id: $this->get('currency_id'),
        );
    }

    public function createCreateCompanyUserCompanyRoleDTO(): CreateCompanyUserCompanyRoleDTO
    {
        return new CreateCompanyUserCompanyRoleDTO(
            company_id: Uuid::fromString($this->get('company_id')),
            role: $this->get('role') ?? '1',

        );
    }
}
