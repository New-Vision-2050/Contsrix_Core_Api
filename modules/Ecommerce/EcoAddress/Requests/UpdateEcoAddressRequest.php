<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoAddress\Commands\UpdateEcoAddressCommand;
use Modules\Ecommerce\EcoAddress\Models\EcoAddress; // Import model
use Illuminate\Validation\Rule;

class UpdateEcoAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'state_id' => ['nullable', 'exists:states,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'type' => ['nullable', 'string', Rule::in(['shipping', 'billing']), 'max:50'],
            'is_default' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.string' => __('ecoaddress::validation.first_name_string'),
            'first_name.max' => __('ecoaddress::validation.first_name_max'),
            'last_name.string' => __('ecoaddress::validation.last_name_string'),
            'last_name.max' => __('ecoaddress::validation.last_name_max'),
            'email.string' => __('ecoaddress::validation.email_string'),
            'email.email' => __('ecoaddress::validation.email_email'),
            'email.max' => __('ecoaddress::validation.email_max'),
            'phone_code.string' => __('ecoaddress::validation.phone_code_string'),
            'phone_code.max' => __('ecoaddress::validation.phone_code_max'),
            'phone.string' => __('ecoaddress::validation.phone_string'),
            'phone.max' => __('ecoaddress::validation.phone_max'),
            'country_id.uuid' => __('ecoaddress::validation.country_id_uuid'),
            'country_id.exists' => __('ecoaddress::validation.country_id_exists'),
            'city_id.uuid' => __('ecoaddress::validation.city_id_uuid'),
            'city_id.exists' => __('ecoaddress::validation.city_id_exists'),
            'state_id.uuid' => __('ecoaddress::validation.state_id_uuid'),
            'state_id.exists' => __('ecoaddress::validation.state_id_exists'),
            'address.string' => __('ecoaddress::validation.address_string'),
            'address.max' => __('ecoaddress::validation.address_max'),
            'address2.string' => __('ecoaddress::validation.address2_string'),
            'address2.max' => __('ecoaddress::validation.address2_max'),
            'zip_code.string' => __('ecoaddress::validation.zip_code_string'),
            'zip_code.max' => __('ecoaddress::validation.zip_code_max'),
            'type.string' => __('ecoaddress::validation.type_string'),
            'type.in' => __('ecoaddress::validation.type_in'),
            'is_default.boolean' => __('ecoaddress::validation.is_default_boolean'),
            'is_default.unique' => __('ecoaddress::validation.is_default_unique'),
        ];
    }

    public function createUpdateEcoAddressCommand(): UpdateEcoAddressCommand
    {
        $validatedData = $this->validated();
        return new UpdateEcoAddressCommand(
            id: Uuid::fromString($this->route('id')),
            firstName: $validatedData['first_name'] ?? null,
            lastName: $validatedData['last_name'] ?? null,
            email: $validatedData['email'] ?? null,
            phoneCode: $validatedData['phone_code'] ?? null,
            phone: $validatedData['phone'] ?? null,
            countryId: (string)$validatedData['country_id'],
            cityId:(string)$validatedData['city_id'],
            stateId: (string)$validatedData['state_id'],
            address: $validatedData['address'] ?? null,
            zipCode: $validatedData['zip_code'] ?? null,
            type: $validatedData['type'] ?? null,
            isDefault:(bool) $validatedData['is_default'] ?? 0,
        );
    }
}
