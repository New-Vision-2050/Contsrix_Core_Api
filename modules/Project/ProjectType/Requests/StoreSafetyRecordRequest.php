<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSafetyRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'morphable_type' => ['required', 'string', Rule::in(['project_notification', 'project_order_permit'])],
            'morphable_id' => ['required', function (string $attribute, mixed $value, \Closure $fail) {
                $type = $this->input('morphable_type');

                if ($type === 'project_notification') {
                    if (! is_string($value) || ! preg_match('/^[0-9a-fA-F-]{36}$/', $value)) {
                        $fail('The morphable_id must be a valid UUID for project_notification.');
                    }
                    return;
                }

                if ($type === 'project_order_permit') {
                    if (! is_numeric($value) || (int) $value < 1) {
                        $fail('The morphable_id must be a valid integer id for project_order_permit.');
                    }
                }
            }],
            'assigned_user_ids' => ['required', 'array', 'min:1'],
            'assigned_user_ids.*' => ['required', 'uuid', 'exists:users,id'],
            'order_type' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'required_score' => ['nullable', 'numeric', 'min:0'],
            'earned_score' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'consultant_engineer' => ['nullable', 'string', 'max:255'],
            'consultant' => ['nullable', 'string', 'max:255'],
            'contractor_id' => ['nullable', 'uuid', 'exists:project_contractors,id'],
            'violations' => ['nullable', 'array'],
            'violations.*.violation_id' => ['required', 'uuid', 'exists:violations,id'],
            'violations.*.weight' => ['nullable', 'numeric'],
            'violations.*.status' => ['nullable', Rule::in([-1, 1, 0])],
        ];
    }
}
