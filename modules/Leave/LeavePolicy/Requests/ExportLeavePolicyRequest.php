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
            'day_calculation_type' => ['sometimes', 'string', 'in:work_day,calendar'],
            'leave_type_id' => ['sometimes', 'uuid', 'exists:leave_types,id'],
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
            'day_calculation_type' => $this->get('day_calculation_type'),
            'leave_type_id' => $this->get('leave_type_id'),
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
            'day_calculation_type.in' => __('leave.export.day_calculation_type.in'),
            'leave_type_id.uuid' => __('leave.export.leave_type_id.uuid'),
            'leave_type_id.exists' => __('leave.export.leave_type_id.exists'),
            'ids.array' => __('leave.export.ids.array'),
            'ids.*.uuid' => __('leave.export.ids.uuid'),
            'ids.*.exists' => __('leave.export.ids.exists'),
        ];
    }
}
