<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoDiscount\DTO\CreateEcoDiscountDTO;

class CreateEcoDiscountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'code' => 'nullable|string|max:50|unique:eco_discounts,code',
            'type' => 'required|in:percentage,fixed_amount,buy_x_get_y',
            'value' => 'required|numeric|min:0|max:999999.99',
            'min_order_amount' => 'nullable|numeric|min:0|max:999999.99',
            'max_discount_amount' => 'nullable|numeric|min:0|max:999999.99',
            'usage_limit' => 'nullable|integer|min:1|max:999999',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
            'applies_to' => 'required|in:all_products,specific_products,categories',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:eco_products,id',
        ];
    }

    public function createCreateEcoDiscountDTO(): CreateEcoDiscountDTO
    {
        return new CreateEcoDiscountDTO(
            name: $this['name'],
            description: $this['description'] ?? null,
            code: $this['code'] ?? null,
            type: $this['type'] ?? 'percentage',
            value: (float) ($this['value'] ?? 0),
            min_order_amount: isset($this['min_order_amount']) ? (float) $this['min_order_amount'] : null,
            max_discount_amount: isset($this['max_discount_amount']) ? (float) $this['max_discount_amount'] : null,
            usage_limit: isset($this['usage_limit']) ? (int) $this['usage_limit'] : null,
            start_date: $this['start_date'] ?? null,
            end_date: $this['end_date'] ?? null,
            is_active: (bool) ($this['is_active'] ?? true),
            applies_to: $this['applies_to'] ?? 'all_products',
            product_ids: $this['product_ids'] ?? [],
        );
    }
}
