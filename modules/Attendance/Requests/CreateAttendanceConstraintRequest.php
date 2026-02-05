<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\DTO\CreateAttendanceConstraintDTO;
use Ramsey\Uuid\UuidInterface;
use Modules\Attendance\Requests\Traits\HasConstraintConfigValidation;
class CreateAttendanceConstraintRequest extends FormRequest
{
    use HasConstraintConfigValidation;
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return $this->user()->can('create_attendance_constraints');
    // }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_ids'         => ['nullable', 'array'],
            'user_ids.*'       => ['uuid', 'exists:users,id'],
            'department_ids'   => ['nullable', 'array'],
            'department_ids.*' => ['exists:management_hierarchies,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['exists:management_hierarchies,id'],

            // Make branch_locations nullable here, its requirement will be conditional in after() validator
            'branch_locations' => ['nullable', 'array'],
            // These rules will validate format if branch_locations is present
            'branch_locations.*.name' => ['required_with:branch_locations.*', 'string', 'max:255'],
            'branch_locations.*.branch_id' => ['required_with:branch_locations.*', 'string', 'max:50'], // Assuming branch_id refers to branch_ids
            'branch_locations.*.address' => ['required_with:branch_locations.*', 'string', 'max:500'],
            'branch_locations.*.latitude' => ['required_with:branch_locations.*', 'numeric', 'between:-90,90'],
            'branch_locations.*.longitude' => ['required_with:branch_locations.*', 'numeric', 'between:-180,180'],
            'branch_locations.*.radius' => ['required_with:branch_locations.*', 'integer', 'min:1', 'max:10000'],

            'constraint_type' => [
                'required',
                'string',
                 'in:' . implode(',', array_keys(AttendanceConstraint::getConstraintArrayTypes()))
            ],
            'constraint_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attendance_constraints', 'constraint_name')
                    ->where('company_id', $this->user()->company_id)
                    ->whereNull('deleted_at'),
            ],
            'constraint_config' => 'nullable|array',
            'is_active' => 'boolean',
            'inherit_from_parent' => ['boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'start_date' => 'nullable|date|after_or_equal:today',
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
            'constraint_name.unique' => 'A constraint with this name already exists for this company.',
            'start_date.after_or_equal' => 'Start date must be today or later.',
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
            if ($this->has('constraint_config') && $this->has('constraint_type')) {
                // Call the shared validation logic from the trait
                $this->validateConstraintConfig($validator);
            }

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
    public function createConstraintDTO(UuidInterface $companyId, UuidInterface $createdBy): CreateAttendanceConstraintDTO
    {
        $validated = $this->validated();

        return new CreateAttendanceConstraintDTO(
            constraint_type: $validated['constraint_type'],
            name: $validated['constraint_name'],
            notes: $validated['notes'] ?? '',
            config: $validated['constraint_config'] ?? [],
            company_id: $companyId,
            created_by: $createdBy,
            user_ids: $validated['user_ids'] ?? [],
            department_ids: $validated['department_ids'] ?? [],
            branch_ids: $validated['branch_ids'] ?? [],
            branch_locations: $validated['branch_locations'] ?? null,
            priority: $validated['priority'] ?? 1,
            is_active: $validated['is_active'] ?? true,
            inherit_from_parent: $validated['inherit_from_parent'] ?? false,
            effective_from: $validated['start_date'] ?? null,
            effective_to: $validated['end_date'] ?? null,
        );
    }
}
