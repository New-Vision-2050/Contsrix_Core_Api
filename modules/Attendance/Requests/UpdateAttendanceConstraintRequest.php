<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\DTO\UpdateAttendanceConstraintDTO;
use Ramsey\Uuid\UuidInterface;

class UpdateAttendanceConstraintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return $this->user()->can('update_attendance_constraints');
    // }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'department_id' => ['nullable', 'string', 'max:255'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['exists:management_hierarchies,id'],
            'branch_locations' => ['nullable', 'array'],
            'branch_locations.*' => ['array'],
            'branch_locations.*.name' => ['required_with:branch_locations.*', 'string', 'max:255'],
            'branch_locations.*.branch_id' => ['nullable', 'string', 'max:50'],
            'branch_locations.*.address' => ['nullable', 'string', 'max:500'],
            'branch_locations.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'branch_locations.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'branch_locations.*.radius' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'constraint_type' => [
                'nullable',
                'required',
                'string',
                // 'in:' . implode(',', array_keys(AttendanceConstraint::getConstraintArrayTypes()))
            ],
            'constraint_name' => 'sometimes|required|string|max:255',
            'constraint_config' => 'sometimes|required|array',
            'is_active' => 'boolean',
            'inherit_from_parent' => ['boolean'],
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
    // public function withValidator($validator): void
    // {
    //     $validator->after(function ($validator) {
    //         if ($this->has('constraint_config')) {
    //             $this->validateConstraintConfig($validator);
    //         }
    //     });
    // }

    /**
     * Validate constraint configuration based on type and name.
     */
    // protected function validateConstraintConfig($validator): void
    // {
    //     $type = $this->input('constraint_type');
    //     $name = $this->input('constraint_name');
    //     $config = $this->input('constraint_config', []);

    //     // Use the same validation logic as CreateAttendanceConstraintRequest
    //     $createRequest = new CreateAttendanceConstraintRequest();
    //     $createRequest->merge($this->all());
    //     $createRequest->validateConstraintConfig($validator);
    // }

    /**
     * Create DTO from validated request data.
     */
    public function createUpdateConstraintDTO(UuidInterface $updatedBy): UpdateAttendanceConstraintDTO
    {
        $validated = $this->validated();

        return new UpdateAttendanceConstraintDTO(
            updated_by: $updatedBy,
            constraint_type: $validated['constraint_type'] ?? null,
            name: $validated['constraint_name'] ?? null,
            description: $validated['notes'] ?? null,
            config: $validated['constraint_config'] ?? [],
            user_id: $validated['user_id'] ?? null,
            department_id: $validated['department_id'] ?? null,
            branch_ids: $validated['branch_ids'] ?? null,
            branch_locations: $validated['branch_locations'] ?? null,
            priority: $validated['priority'] ?? null,
            is_active: $validated['is_active'] ?? null,
            inherit_from_parent: $validated['inherit_from_parent'] ?? null,
            effective_from: $validated['start_date'] ?? null,
            effective_to: $validated['end_date'] ?? null,
        );
    }
}
