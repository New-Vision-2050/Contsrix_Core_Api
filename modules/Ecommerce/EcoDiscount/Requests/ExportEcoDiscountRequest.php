<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportEcoDiscountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'format' => 'nullable|in:xlsx,csv,pdf',
            'filters' => 'nullable|array',
            'filters.search' => 'nullable|string',
            'filters.is_active' => 'nullable|boolean',
            'filters.type' => 'nullable|in:percentage,fixed_amount,buy_x_get_y',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after:filters.date_from',
        ];
    }

    public function getFilters(): array
    {
        return $this['filters'] ?? [];
    }
}
