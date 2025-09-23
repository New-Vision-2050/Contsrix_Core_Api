<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAddress\DTO\CreateEcoAddressDTO;
use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;
use Modules\Ecommerce\EcoAddress\DTO\Dashboard\CreateEcoAddressDashboardDTO;

class CreateEcoAddressDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = tenant("id");
        $ecoClientId = $this->input('eco_client_id');

        return [
            'eco_client_id' => ['required', 'uuid', 'exists:eco_clients,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:20'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'state_id' => ['required', 'exists:states,id'],
            'address' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'type' => ['required', 'string', Rule::in(['shipping', 'billing']), 'max:50'],
            'is_default' => [
                'boolean',
                Rule::unique('eco_addresses')
                    ->where(function ($query) use ($companyId, $ecoClientId) {
                        return $query->where('company_id', $companyId)
                                     ->where('eco_client_id', $ecoClientId)
                                     ->where('is_default', 1);
                    })
                    ->ignore(true, 'is_default')
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'eco_client_id.required' => __('ecoaddress::validation.eco_client_id_required'),
            'eco_client_id.uuid' => __('ecoaddress::validation.eco_client_id_uuid'),
            'eco_client_id.exists' => __('ecoaddress::validation.eco_client_id_exists'),
            'first_name.required' => __('ecoaddress::validation.first_name_required'),
            'first_name.string' => __('ecoaddress::validation.first_name_string'),
            'first_name.max' => __('ecoaddress::validation.first_name_max'),
            'last_name.required' => __('ecoaddress::validation.last_name_required'),
            'last_name.string' => __('ecoaddress::validation.last_name_string'),
            'last_name.max' => __('ecoaddress::validation.last_name_max'),
            'email.required' => __('ecoaddress::validation.email_required'),
            'email.string' => __('ecoaddress::validation.email_string'),
            'email.email' => __('ecoaddress::validation.email_email'),
            'email.max' => __('ecoaddress::validation.email_max'),
            'phone_code.required' => __('ecoaddress::validation.phone_code_required'),
            'phone_code.string' => __('ecoaddress::validation.phone_code_string'),
            'phone_code.max' => __('ecoaddress::validation.phone_code_max'),
            'phone.required' => __('ecoaddress::validation.phone_required'),
            'phone.string' => __('ecoaddress::validation.phone_string'),
            'phone.max' => __('ecoaddress::validation.phone_max'),
            'country_id.required' => __('ecoaddress::validation.country_id_required'),
            'country_id.uuid' => __('ecoaddress::validation.country_id_uuid'),
            'country_id.exists' => __('ecoaddress::validation.country_id_exists'),
            'city_id.required' => __('ecoaddress::validation.city_id_required'),
            'city_id.uuid' => __('ecoaddress::validation.city_id_uuid'),
            'city_id.exists' => __('ecoaddress::validation.city_id_exists'),
            'state_id.required' => __('ecoaddress::validation.state_id_required'),
            'state_id.uuid' => __('ecoaddress::validation.state_id_uuid'),
            'state_id.exists' => __('ecoaddress::validation.state_id_exists'),
            'address.required' => __('ecoaddress::validation.address_required'),
            'address.string' => __('ecoaddress::validation.address_string'),
            'address.max' => __('ecoaddress::validation.address_max'),
            'address2.string' => __('ecoaddress::validation.address2_string'),
            'address2.max' => __('ecoaddress::validation.address2_max'),
            'zip_code.string' => __('ecoaddress::validation.zip_code_string'),
            'zip_code.max' => __('ecoaddress::validation.zip_code_max'),
            'type.required' => __('ecoaddress::validation.type_required'),
            'type.string' => __('ecoaddress::validation.type_string'),
            'type.in' => __('ecoaddress::validation.type_in'),
            'is_default.boolean' => __('ecoaddress::validation.is_default_boolean'),
            'is_default.unique' => __('ecoaddress::validation.is_default_unique'),
        ];
    }

    public function createCreateEcoAddressDTO(): CreateEcoAddressDashboardDTO
    {

        $validatedData = $this->validated();
        return new CreateEcoAddressDashboardDTO(
            companyId: Uuid::fromString(tenant("id")),
            ecoClientId: $validatedData['eco_client_id'],
            firstName: $validatedData['first_name'],
            lastName: $validatedData['last_name'],
            email: $validatedData['email'],
            phoneCode: $validatedData['phone_code'],
            phone: $validatedData['phone'],
            countryId: (string)$validatedData['country_id'],
            cityId: (string)$validatedData['city_id'],
            stateId: (string)$validatedData['state_id'],
            address: $validatedData['address'],
            zipCode: $validatedData['zip_code'] ?? null,
            type: $validatedData['type'] ?? 'shipping',
            isDefault: (bool)$validatedData['is_default'] ?? 0,
        );
    }
}
