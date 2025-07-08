<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\FilterAttendanceConstraintDTO;

class FilterConstraintsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'constraint_type' => ['sometimes', 'string'],
            'name' => ['sometimes', 'string'],
            'user_id' => ['sometimes', 'string', 'exists:users,id'],
            'department_id' => ['sometimes', 'string', 'exists:departments,id'],
            'priority_from' => ['sometimes', 'integer', 'min:1', 'max:10', 'lte:priority_to'],
            'priority_to' => ['sometimes', 'integer', 'min:1', 'max:10', 'gte:priority_from'],
            'effective_from' => ['sometimes', 'date', 'before_or_equal:effective_to'],
            'effective_to' => ['sometimes', 'date', 'after_or_equal:effective_from'],
            'user_name' => ['sometimes', 'string'],
            'user_email' => ['sometimes', 'string', 'email'],
            'company_name' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            // 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            // 'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'The selected user does not exist.',
            'department_id.exists' => 'The selected department does not exist.',
            'priority_from.lte' => 'The minimum priority must be less than or equal to the maximum priority.',
            'priority_to.gte' => 'The maximum priority must be greater than or equal to the minimum priority.',
            'effective_from.before_or_equal' => 'The effective start date must be before or equal to the end date.',
            'effective_to.after_or_equal' => 'The effective end date must be after or equal to the start date.',
            'user_email.email' => 'The user email must be a valid email address.',
            'per_page.max' => 'The per page value cannot exceed 100.',
        ];
    }

    /**
     * Create DTO from validated request data.
     */
    public function createFilterConstraintDTO(string $companyId): FilterAttendanceConstraintDTO
    {
        $validated = $this->validated();

        return new FilterAttendanceConstraintDTO(
            company_id: $companyId,
            constraint_type: $validated['constraint_type'] ?? null,
            name: $validated['name'] ?? null,
            user_id: $validated['user_id'] ?? null,
            department_id: $validated['department_id'] ?? null,
            priority_from: $validated['priority_from'] ?? null,
            priority_to: $validated['priority_to'] ?? null,
            effective_from: $validated['effective_from'] ?? null,
            effective_to: $validated['effective_to'] ?? null,
            user_name: $validated['user_name'] ?? null,
            user_email: $validated['user_email'] ?? null,
            company_name: $validated['company_name'] ?? null,
            is_active: $validated['is_active'] ?? null,
            // page: $validated['page'] ?? 1,
            // per_page: $validated['per_page'] ?? 10,
        );
    }
}
