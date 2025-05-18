<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Broker;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\SetUserAddressDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Rules\CompanyUserValidation;
use Modules\CompanyUser\Rules\PhoneEmailConsistencyRule;
use Modules\CompanyUser\Rules\UserNameValidation;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;

class CreateBrokerRequest extends FormRequest
{


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', new UserNameValidation()],

            //start user national address

            'country_id' => 'nullable|exists:countries,id',
            "state_id" => "nullable|exists:states,id",
            "city_id" => "nullable|exists:cities,id",
            "neighborhood_name" => "nullable",
            "street_name" => "nullable",
            "building_number" => "nullable",
            "additional_phone" => "nullable",
            "postal_code" => "nullable",

            //end user national address

            'phone' => [
                'required',"phone"
//                , new PhoneEmailConsistencyRule($this->input('email'))
            ],
            'email' => [
                'required',
                'email'
            ],
            'residence' => 'nullable',
            "branch_ids" => "nullable|array",
            "branch_ids.*" => "exists:management_hierarchies,id,type,branch"


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

    public function createCreateBrokerDTO(): CreateBrokerDTO
    {
        return new CreateBrokerDTO(
            name: $this->get("name"),
            email: $this->get('email'),
            countryId: $this->get('country_id'),
            phone: $this->get('phone'),
            residence: $this->get('residence'),
            branchIds: $this->get('branch_ids'),
        );
    }
    public function createSetUserAddressDTO(): SetUserAddressDTO
    {
        return new SetUserAddressDTO(

            countryId: $this->get('country_id'),
            stateId: $this->get('state_id'),
            cityId: $this->get('city_id'),
            neighborhoodName: $this->get('neighborhood_name'),
            streetName: $this->get('street_name'),
            buildingNumber: $this->get('building_number'),
            additionalPhone: $this->get('additional_phone'),
            postalCode: $this->get('postal_code'),

        );
    }

    public function createCreateCompanyUserCompanyRoleDTO(): CreateCompanyUserCompanyRoleDTO
    {
        return new CreateCompanyUserCompanyRoleDTO(
            company_id: Uuid::fromString( tenant("id")),//will create for current company
            role: (string)CompanyUserRole::BROKER->value,

        );
    }
}
