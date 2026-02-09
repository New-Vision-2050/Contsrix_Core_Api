<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\Client\CreateClientCompanyDTO;
use Modules\CompanyUser\DTO\Client\CreateClientDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
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

class CreateClientCompanyRequest extends FormRequest
{


    public function rules(): array
    {

        return [
            'user_id' => ['required',"exists:users,id"],


            "company_id"=>"require|exists:companies,id"




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

    public function CreateClientCompanyDTO():CreateClientCompanyDTO
    {
        return new CreateClientCompanyDTO(
            userId: Uuid::fromString($this->get("user_id")),
            companyId: Uuid::fromString($this->get("company_id")),
        );
    }


}
