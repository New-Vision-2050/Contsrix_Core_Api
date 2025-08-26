<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportLeavePolicyRequest extends FormRequest
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
            'total_days' => ['sometimes', 'integer', 'min:0'],
            'day_type' => ['sometimes', 'string', 'max:100'],
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['uuid', 'exists:leave_policies,id'],
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
            'total_days' => $this->get('total_days'),
            'day_type' => $this->get('day_type'),
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
            'total_days.integer' => __('leave.export.total_days.integer'),
            'total_days.min' => __('leave.export.total_days.min'),
            'day_type.string' => __('leave.export.day_type.string'),
            'day_type.max' => __('leave.export.day_type.max'),
            'ids.array' => __('leave.export.ids.array'),
            'ids.*.uuid' => __('leave.export.ids.uuid'),
            'ids.*.exists' => __('leave.export.ids.exists'),
        ];
    }
}
