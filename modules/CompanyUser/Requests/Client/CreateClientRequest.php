<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
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

class CreateClientRequest extends FormRequest
{


    public function rules(): array
    {
        $email = $this->input('email');

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
                , new PhoneEmailConsistencyRule($email)
            ],
            'email' => [
                'required',
                'email'
            ],
            'residence' => ['nullable', new ResidenceValidationRule($email)],
            "branch_ids" => "required|array",
            "branch_ids.*" => "exists:management_hierarchies,id,type,branch",
            "latitude" => "nullable",
            "longitude" => "nullable",
            'type' => 'required|integer',
            'registration_number' => 'nullable|string',
            "company_representative_name" => 'nullable|string',
            "broker_id" => 'nullable|string|exists:users,id',
            "message_address"=>"nullable|email"



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

    public function createCreateClientDTO(): CreateClientDTO
    {
        return new CreateClientDTO(
            name: $this->get("name"),
            email: $this->get('email'),
            countryId: $this->get('country_id'),
            phone: $this->get('phone'),
            residence: $this->get('residence'),
            branchIds: $this->get('branch_ids'),
            brokerId:$this->get("broker_id")!=null?Uuid::fromString( $this->get("broker_id")):null,
            type: (int)$this->get("type"),
            registrationNumber: (string)$this->get("registration_number"),
            companyRepresentativeName: $this->get("company_representative_name"),
            messageAddress: $this->get("message_address")
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
            latitude: $this->get("latitude"),
            longitude: $this->get("longitude"),


        );
    }

    public function createCreateCompanyUserCompanyRoleDTO(): CreateCompanyUserCompanyRoleDTO
    {
        return new CreateCompanyUserCompanyRoleDTO(
            company_id: Uuid::fromString( tenant("id")),//will create for current company
            role: (string)CompanyUserRole::CLIENT->value,

        );
    }
}
