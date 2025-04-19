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

    public function createAssignLogoToCompanyDTO(): SetCompanyAddressCommand
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

