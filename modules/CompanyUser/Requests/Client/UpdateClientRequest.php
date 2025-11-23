<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\Client\CreateClientDTO;
use Modules\CompanyUser\DTO\Client\UpdateClientDTO;
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

class UpdateClientRequest extends FormRequest
{


    public function rules(): array
    {

        return [

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

            "branch_ids" => "required|array",
            "branch_ids.*" => "exists:management_hierarchies,id,type,branch",
            "latitude" => "nullable",
            "longitude" => "nullable",
            'registration_number' => 'nullable|string',
            "company_representative_name" => 'nullable|string',
            "broker_id" => 'nullable|string|exists:users,id',
            "message_address"=>"nullable|email",




        ];
    }



    public function createUpdateClientDTO(): UpdateClientDTO
    {
        return new UpdateClientDTO(
            id: $this->route('id'),
            countryId: $this->get('country_id'),
            branchIds: $this->get('branch_ids'),
            brokerId:$this->get("broker_id")!=null?Uuid::fromString( $this->get("broker_id")):null,
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


}
