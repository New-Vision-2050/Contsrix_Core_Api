<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLeaveRequestsRequest extends FormRequest
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
            'company_id' => ['sometimes', 'string', 'exists:companies,id'],
            'leave_type_id' => ['sometimes', 'string', 'exists:leave_types,id'],
            'status' => ['sometimes', 'string', 'in:pending,approved,rejected,cancelled'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'is_emergency' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort_by' => ['sometimes', 'string', 'in:start_date,end_date,created_at,status'],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'The selected user does not exist.',
            'company_id.exists' => 'The selected company does not exist.',
            'leave_type_id.exists' => 'The selected leave type does not exist.',
            'status.in' => 'Status must be one of: pending, approved, rejected, cancelled.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.max' => 'Per page value cannot exceed 100.',
            'sort_by.in' => 'Sort by must be one of: start_date, end_date, created_at, status.',
            'sort_order.in' => 'Sort order must be either asc or desc.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values if not provided
        if (!$this->has('per_page')) {
            $this->merge(['per_page' => 20]);
        }

        if (!$this->has('page')) {
            $this->merge(['page' => 1]);
        }

        if (!$this->has('sort_by')) {
            $this->merge(['sort_by' => 'created_at']);
        }

        if (!$this->has('sort_order')) {
            $this->merge(['sort_order' => 'desc']);
        }

        // Convert boolean strings to actual booleans
        if ($this->has('is_emergency') && is_string($this->input('is_emergency'))) {
            $this->merge(['is_emergency' => filter_var($this->input('is_emergency'), FILTER_VALIDATE_BOOLEAN)]);
        }

        // If company_id is not provided, use tenant ID
        if (!$this->has('company_id') && function_exists('tenant')) {
            $this->merge(['company_id' => tenant('id')]);
        }
    }

    /**
     * Get the validated data with defaults applied
     */
    public function getFilters(): array
    {
        $validated = $this->validated();
        
        // Apply default date range if not provided (last 3 months)
        if (!isset($validated['start_date']) && !isset($validated['end_date'])) {
            $validated['start_date'] = now()->subMonths(3)->toDateString();
            $validated['end_date'] = now()->toDateString();
        }

        return $validated;
    }
}
