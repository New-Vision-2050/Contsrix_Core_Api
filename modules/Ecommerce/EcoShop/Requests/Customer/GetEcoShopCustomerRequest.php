<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoShopCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
