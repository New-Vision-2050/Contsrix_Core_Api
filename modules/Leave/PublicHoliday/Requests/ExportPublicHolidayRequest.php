<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportPublicHolidayRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['sometimes', Rule::in(['xlsx', 'csv'])],
            'name' => ['sometimes', 'string', 'max:255'],
            'country_id' => ['sometimes', 'exists:countries,id'],
            'date_start' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'date_end' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['uuid', 'exists:public_holidays,id'],
        ];
    }

    /**
     * Get filters from the request
     *
     * @return array
     */
    public function getFilters(): array
    {
        return array_filter([
            'name' => $this->get('name'),
            'country_id' => $this->get('country_id'),
            'date_start' => $this->get('date_start'),
            'date_end' => $this->get('date_end'),
            'ids' => $this->get('ids'),
        ]);
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'format.in' => __('leave.export.format.invalid'),
            'name.string' => __('leave.export.name.string'),
            'name.max' => __('leave.export.name.max'),
            'country_id.exists' => __('leave.export.country_id.exists'),
            'date_start.date' => __('leave.export.date_start.date'),
            'date_start.date_format' => __('leave.export.date_start.date_format'),
            'date_end.date' => __('leave.export.date_end.date'),
            'date_end.date_format' => __('leave.export.date_end.date_format'),
            'ids.array' => __('leave.export.ids.array'),
            'ids.*.uuid' => __('leave.export.ids.uuid'),
            'ids.*.exists' => __('leave.export.ids.exists'),
        ];
    }
}
