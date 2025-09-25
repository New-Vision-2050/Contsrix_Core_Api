<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoAddressListCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:shipping,billing'],
            'client_id' => ['nullable', 'uuid', 'exists:eco_clients,id'], // For admin access
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
