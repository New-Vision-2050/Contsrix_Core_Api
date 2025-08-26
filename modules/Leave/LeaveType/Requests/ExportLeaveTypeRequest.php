<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportLeaveTypeRequest extends FormRequest
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
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['uuid', 'exists:leave_types,id'],
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
            'ids.array' => __('leave.export.ids.array'),
            'ids.*.uuid' => __('leave.export.ids.uuid'),
            'ids.*.exists' => __('leave.export.ids.exists'),
        ];
    }
}
