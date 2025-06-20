<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\Models\AttendanceConstraint;

class UpdateAttendanceConstraintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update_attendance_constraints');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|uuid|exists:users,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'constraint_type' => [
                'sometimes',
                'required',
                'string',
                'in:' . implode(',', array_keys(AttendanceConstraint::getConstraintTypes()))
            ],
            'constraint_name' => 'sometimes|required|string|max:255',
            'constraint_config' => 'sometimes|required|array',
            'is_active' => 'boolean',
            'priority' => 'integer|min:1|max:10',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'constraint_type.in' => 'The selected constraint type is invalid.',
            'constraint_config.required' => 'Constraint configuration is required.',
            'end_date.after' => 'End date must be after start date.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority cannot exceed 10.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('constraint_config')) {
                $this->validateConstraintConfig($validator);
            }
        });
    }

    /**
     * Validate constraint configuration based on type and name.
     */
    protected function validateConstraintConfig($validator): void
    {
        $type = $this->input('constraint_type');
        $name = $this->input('constraint_name');
        $config = $this->input('constraint_config', []);

        // Use the same validation logic as CreateAttendanceConstraintRequest
        $createRequest = new CreateAttendanceConstraintRequest();
        $createRequest->merge($this->all());
        $createRequest->validateConstraintConfig($validator);
    }
}
