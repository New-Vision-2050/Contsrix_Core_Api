<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\FilterViolationDTO;

class GetViolationsRequest extends FormRequest
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
            'user_id' => ['sometimes', 'string', 'exists:users,id'],
            'constraint_id' => ['sometimes', 'string', 'exists:attendance_constraints,id'],
            'severity' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
            'violation_type' => ['sometimes', 'string'],
            'detected_from' => ['sometimes', 'date', 'before_or_equal:detected_to'],
            'detected_to' => ['sometimes', 'date', 'after_or_equal:detected_from'],
            'resolved_by' => ['sometimes', 'string', 'exists:users,id'],
            'user_name' => ['sometimes', 'string'],
            'user_email' => ['sometimes', 'string', 'email'],
            'constraint_name' => ['sometimes', 'string'],
            'critical' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'The selected user does not exist.',
            'constraint_id.exists' => 'The selected constraint does not exist.',
            'resolved_by.exists' => 'The selected resolver does not exist.',
            'detected_from.before_or_equal' => 'The detection start date must be before or equal to the end date.',
            'detected_to.after_or_equal' => 'The detection end date must be after or equal to the start date.',
            'user_email.email' => 'The user email must be a valid email address.',
            'per_page.max' => 'The per page value cannot exceed 100.',
        ];
    }

    /**
     * Create DTO from validated request data.
     */
    public function createFilterViolationDTO(string $companyId): FilterViolationDTO
    {
        $validated = $this->validated();
        
        return new FilterViolationDTO(
            company_id: $companyId,
            user_id: $validated['user_id'] ?? null,
            constraint_id: $validated['constraint_id'] ?? null,
            severity: $validated['severity'] ?? null,
            status: $validated['status'] ?? null,
            violation_type: $validated['violation_type'] ?? null,
            detected_from: $validated['detected_from'] ?? null,
            detected_to: $validated['detected_to'] ?? null,
            resolved_by: $validated['resolved_by'] ?? null,
            user_name: $validated['user_name'] ?? null,
            user_email: $validated['user_email'] ?? null,
            constraint_name: $validated['constraint_name'] ?? null,
            critical: $validated['critical'] ?? null,
            page: $validated['page'] ?? null,
            per_page: $validated['per_page'] ?? null,
        );
    }
}
