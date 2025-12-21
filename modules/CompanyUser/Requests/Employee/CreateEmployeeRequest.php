<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\Employee\CreateEmployeeDTO;
use Modules\CompanyUser\DTO\SetUserAddressDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Rules\CompanyUserValidation;
use Modules\CompanyUser\Rules\PhoneEmailConsistencyRule;
use Modules\CompanyUser\Rules\UserNameValidation;
use Modules\CompanyUser\Rules\ResidenceValidationRule;
use Modules\CompanyUser\Rules\PassportValidationRule;
use Modules\CompanyUser\Rules\IdentityValidationRule;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;

class CreateEmployeeRequest extends FormRequest
{


    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', new UserNameValidation()],
            'last_name' => ['required', 'string', new UserNameValidation()],


            'phone' => [
                'required', "phone"
                , new PhoneEmailConsistencyRule($this->input('email'))
            ],
            'email' => [
                'required',
                'email'
            ],
            "branch_id" => "nullable|exists:management_hierarchies,id,type,branch",
            'job_title_id' => 'nullable|exists:job_titles,id',
            "status" => 'nullable|in:1,0',
            'country_id' => 'nullable|exists:countries,id',



        ];
    }

    public function messages(): array //TODO not good for me:@AmrSaleh get from past i will add it validation.php
    {
        return [
            'name.required' => __('validation.company_user.first_name_required'),
            'phone.required' => __('validation.company_user.phone_required'),
            'email.required' => __('validation.company_user.email_required'),
            'email.email' => __('validation.company_user.email_invalid'),

            'residence.unique' => __('validation.company_user.residence_unique'),

        ];
    }

    public function createCreateEmployeeDTO(): CreateEmployeeDTO
    {
        return new CreateEmployeeDTO(
            firstName: (string) $this->get("first_name"),
            lastName:(string) $this->get("last_name"),
            email: (string)$this->get('email'),
            countryId: $this->get('country_id')!=null?(string)$this->get('country_id'):null,
            phone: (string)$this->get('phone'),
            jobTitleId: (string)$this->get("job_title_id"),
            status:(int) $this->get("status"),
            branchId:(int) $this->get('branch_id'),
        );
    }

    public function createCreateCompanyUserCompanyRoleDTO(): CreateCompanyUserCompanyRoleDTO
    {
        return new CreateCompanyUserCompanyRoleDTO(
            company_id: Uuid::fromString(tenant("id")),//will create for current company
            role: (string)CompanyUserRole::EMPLOYEE->value,
            subEntityId: $this->get("sub_entity_id"),
            status:(int) $this->get("status"),

        );
    }
}
