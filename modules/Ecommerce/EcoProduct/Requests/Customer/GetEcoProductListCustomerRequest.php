<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoProductListCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'category_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
            'brand_id' => ['nullable', 'uuid', 'exists:eco_brands,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:name,price,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
