<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Commands\CompanyProfile\SetCompanyAddressCommand;
use Modules\Company\CompanyCore\DTO\CompanyProfile\AssignLogoToCompanyDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Ramsey\Uuid\Uuid;

class SetCompanyAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country_id' => 'required|exists:countries,id',
            "state_id" => "required|exists:states,id",
            "city_id" => "required|exists:cities,id",
            "neighborhood_name" => "required",
            "street_name" => "required",
            "building_number" => "required",
            "additional_phone" => "required",
            "postal_code" => "required",
            ];
    }

    public function messages(): array
    {
        return [
            'country_id.required' => __('validation.address.country_id.required'),
            'country_id.exists' => __('validation.address.country_id.exists'),
            'state_id.required' => __('validation.address.state_id.required'),
            'state_id.exists' => __('validation.address.state_id.exists'),
            'city_id.required' => __('validation.address.city_id.required'),
            'city_id.exists' => __('validation.address.city_id.exists'),
            'neighborhood_name.required' => __('validation.address.neighborhood_name.required'),
            'street_name.required' => __('validation.address.street_name.required'),
            'building_number.required' => __('validation.address.building_number.required'),
            'additional_phone.required' => __('validation.address.additional_phone.required'),
            'postal_code.required' => __('validation.address.postal_code.required'),
        ];
    }
    public function createSetCompanyAddressCommand(): SetCompanyAddressCommand
    {
        return new SetCompanyAddressCommand(
            id: Uuid::fromString($this->route("id")),
            countryId: $this->get('country_id'),
            stateId: $this->get('state_id'),
            cityId: $this->get('city_id'),
            neighborhoodName: $this->get('neighborhood_name'),
            streetName: $this->get('street_name'),
            buildingNumber: $this->get('building_number'),
            addtionalPhone: $this->get('additional_phone'),
            postalCode: $this->get('postal_code'),
        );
    }
}

