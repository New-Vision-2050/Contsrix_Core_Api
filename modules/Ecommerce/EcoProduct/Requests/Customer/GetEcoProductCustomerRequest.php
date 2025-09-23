<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoProductCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:eco_products,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
