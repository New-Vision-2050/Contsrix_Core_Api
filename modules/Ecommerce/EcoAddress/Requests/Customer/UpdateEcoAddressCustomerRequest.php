<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAddress\DTO\UpdateEcoAddressDTO;
use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;

class UpdateEcoAddressCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
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
            'is_default' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('Address first name is required'),
            'first_name.string' => __('Address first name must be a string'),
            'first_name.max' => __('Address first name cannot exceed 255 characters'),
            'last_name.required' => __('Address last name is required'),
            'last_name.string' => __('Address last name must be a string'),
            'last_name.max' => __('Address last name cannot exceed 255 characters'),
            'email.required' => __('Address email is required'),
            'email.email' => __('Address email must be a valid email address'),
            'email.max' => __('Address email cannot exceed 255 characters'),
            'phone_code.required' => __('Phone code is required'),
            'phone_code.string' => __('Phone code must be a string'),
            'phone_code.max' => __('Phone code cannot exceed 10 characters'),
            'phone.required' => __('Phone number is required'),
            'phone.string' => __('Phone number must be a string'),
            'phone.max' => __('Phone number cannot exceed 20 characters'),
            'country_id.required' => __('Country is required'),
            'country_id.exists' => __('Selected country does not exist'),
            'city_id.required' => __('City is required'),
            'city_id.exists' => __('Selected city does not exist'),
            'state_id.required' => __('State is required'),
            'state_id.exists' => __('Selected state does not exist'),
            'address.required' => __('Address is required'),
            'address.string' => __('Address must be a string'),
            'address.max' => __('Address cannot exceed 255 characters'),
            'zip_code.string' => __('Zip code must be a string'),
            'zip_code.max' => __('Zip code cannot exceed 20 characters'),
            'type.required' => __('Address type is required'),
            'type.string' => __('Address type must be a string'),
            'type.in' => __('Address type must be either shipping or billing'),
            'type.max' => __('Address type cannot exceed 50 characters'),
            'is_default.boolean' => __('Default address flag must be true or false'),
        ];
    }

    public function toDTO(): UpdateEcoAddressDTO
    {
        return new UpdateEcoAddressDTO(
            id: Uuid::fromString($this->route('id')),
            companyId: Uuid::fromString(tenant("id")),
            ecoClientId: auth('sanctum')->user()->id ?? $this->input('eco_client_id'),
            firstName: $this->input('first_name'),
            lastName: $this->input('last_name'),
            email: $this->input('email'),
            phoneCode: $this->input('phone_code'),
            phone: $this->input('phone'),
            countryId: $this->input('country_id'),
            cityId: $this->input('city_id'),
            stateId: $this->input('state_id'),
            address: $this->input('address'),
            zipCode: $this->input('zip_code'),
            type: $this->input('type', 'shipping'),
            isDefault: $this->boolean('is_default', false),
        );
    }

    public function authorize(): bool
    {
        return true;
    }
}
