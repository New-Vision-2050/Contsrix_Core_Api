<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoAddressCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:eco_addresses,id'],
            'client_id' => ['nullable', 'uuid', 'exists:eco_clients,id'], // For admin access
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
