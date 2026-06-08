<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\DTO\UpdateAttendanceConstraintDTO;
use Ramsey\Uuid\UuidInterface;
use Modules\Attendance\Requests\Traits\HasConstraintConfigValidation;
use InvalidArgumentException;
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;


class UpdateAttendanceConstraintRequest extends FormRequest
{
     use HasConstraintConfigValidation;
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
            // UIDs for relationships should be 'nullable' and 'array' if not always sent,
            // but 'uuid' and 'exists' if sent. 'sometimes' means apply if present.
            'user_ids'         => ['nullable', 'array', 'sometimes'],
            'user_ids.*'       => ['uuid', 'exists:users,id'],
            'department_ids'   => ['nullable', 'array', 'sometimes'],
            'department_ids.*' => ['exists:management_hierarchies,id'],
            'branch_ids' => ['nullable', 'array', 'sometimes'],
            'branch_ids.*' => ['exists:management_hierarchies,id'],

            // branch_locations structure. Make top level 'sometimes|nullable|array'
            'branch_locations' => ['sometimes', 'nullable', 'array'],
            // Sub-rules for branch_locations.* apply IF 'branch_locations' is present AND an array.
            // 'required_with:branch_locations.*' implies that if branch_locations array has elements,
            // these fields are required for each element. This is generally correct.
            'branch_locations.*.name' => ['required_with:branch_locations.*', 'string', 'max:255'],
            'branch_locations.*.branch_id' => ['required_with:branch_locations.*', 'max:50'],
            'branch_locations.*.address' => ['required_with:branch_locations.*', 'string', 'max:500'],
            'branch_locations.*.latitude' => ['required_with:branch_locations.*', 'numeric', 'between:-90,90'],
            'branch_locations.*.longitude' => ['required_with:branch_locations.*', 'numeric', 'between:-180,180'],
            'branch_locations.*.radius' => ['required_with:branch_locations.*', 'integer', 'min:1', 'max:10000'],

            'constraint_type' => [
                'sometimes', // يمكن أن يتم تحديثه أحيانًا
                'required',  // إذا تم إرساله، فهو مطلوب (ليس null)
                'string',
                'in:' . implode(',', array_keys(AttendanceConstraint::getConstraintArrayTypes()))
            ],
            'constraint_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('attendance_constraints', 'constraint_name')
                    ->where('company_id', $this->user()->company_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->route('constraint')),
            ],
            'constraint_config' => 'sometimes|required|array',
            'max_over_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'out_zone_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_working_hours' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:24'],
            'is_active' => 'sometimes|boolean',
            'inherit_from_parent' => ['sometimes', 'boolean'],
            'priority' => 'sometimes|integer|min:1|max:10',
            'start_date' => 'nullable|date|sometimes',
            'end_date' => 'nullable|date|after:start_date|sometimes',
            'notes' => 'nullable|string|max:1000|sometimes',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'constraint_type.in' => 'The selected constraint type is invalid.',
            'constraint_name.unique' => 'A constraint with this name already exists for this company.',
            'constraint_config.required' => 'Constraint configuration is required when provided.', // Updated message
            'end_date.after' => 'End date must be after start date.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority cannot exceed 10.',
            'constraint_config.time_rules.weekly_schedule.required' => 'The weekly schedule is required for multiple periods configuration.',
            'branch_locations.required_when_location_enabled' => 'Branch locations are required when location attendance type is enabled.',
            'branch_locations.*.name.required_with' => 'Branch location name is required.',
            'branch_locations.*.branch_id.required_with' => 'Branch location ID is required.',
            'branch_locations.*.address.required_with' => 'Branch location address is required.',
            'branch_locations.*.latitude.required_with' => 'Branch location latitude is required.',
            'branch_locations.*.longitude.required_with' => 'Branch location longitude is required.',
            'branch_locations.*.radius.required_with' => 'Branch location radius is required and must be a positive integer.',
        ];
    }

   /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Only validate constraint_config details if it's explicitly present in the request
            // and if constraint_type is also present (as config validation depends on type)
            if ($this->has('constraint_config') && $this->has('constraint_type')) {
                // Call the shared validation logic from the trait
                $this->validateConstraintConfig($validator);
            }

            // Additional cross-field validation for dates if both are present
            if ($this->has('start_date') && $this->has('end_date')) {
                if ($this->input('start_date') && $this->input('end_date') && $this->input('start_date') >= $this->input('end_date')) {
                    $validator->errors()->add('end_date', 'End date must be after start date.');
                }
            }
        });
    }


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
            notes: $validated['notes'] ?? null,
            config: $validated['constraint_config'] ?? null,
            user_ids: $validated['user_ids'] ?? null,
            department_ids: $validated['department_ids'] ?? null,
            branch_ids: $validated['branch_ids'] ?? null,
            branch_locations: $validated['branch_locations'] ?? null,
            priority: $validated['priority'] ?? null,
            is_active: $validated['is_active'] ?? null,
            inherit_from_parent: $validated['inherit_from_parent'] ?? null,
            effective_from: $validated['start_date'] ?? null,
            effective_to: $validated['end_date'] ?? null,
            max_over_time: array_key_exists('max_over_time', $validated) ? (isset($validated['max_over_time']) ? (int) $validated['max_over_time'] : null) : null,
            out_zone_minutes: array_key_exists('out_zone_minutes', $validated) ? (isset($validated['out_zone_minutes']) ? (int) $validated['out_zone_minutes'] : null) : null,
            max_working_hours: array_key_exists('max_working_hours', $validated) ? (isset($validated['max_working_hours']) ? (int) $validated['max_working_hours'] : null) : null,
        );
    }
}
