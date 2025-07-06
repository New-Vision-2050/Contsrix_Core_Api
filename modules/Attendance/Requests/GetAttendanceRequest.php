<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\FilterAttendanceDTO;

class GetAttendanceRequest extends FormRequest
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
            'start_date' => ['sometimes', 'date', 'before_or_equal:end_date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', 'in:active,completed,pending_approval,approved,rejected'],
            'is_late' => ['sometimes', 'boolean'],
            'is_early_departure' => ['sometimes', 'boolean'],
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
            'company_id.exists' => 'The selected company does not exist.',
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'status.in' => 'The status must be one of: active, completed, pending_approval, approved, rejected.',
            'per_page.max' => 'The per page value cannot exceed 100.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values if not provided
        // if (!$this->has('per_page')) {
        //     $this->merge(['per_page' => 20]);
        // }

        // if (!$this->has('page')) {
        //     $this->merge(['page' => 1]);
        // }

        // If user_id is not provided, use the authenticated user's ID
        //if (!$this->has('user_id') && $this->user()) {
        //    $this->merge(['user_id' => $this->user()->id->toString()]);
        //}

        // Convert boolean strings to actual booleans
        if ($this->has('is_late') && is_string($this->input('is_late'))) {
            $this->merge(['is_late' => filter_var($this->input('is_late'), FILTER_VALIDATE_BOOLEAN)]);
        }

        if ($this->has('is_early_departure') && is_string($this->input('is_early_departure'))) {
            $this->merge(['is_early_departure' => filter_var($this->input('is_early_departure'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * Get the validated data with defaults applied
     */
    public function getFilters(): array
    {
        $validated = $this->validated();

        // Apply default date range if not provided (last 30 days)
        if (!isset($validated['start_date']) && !isset($validated['end_date'])) {
            $validated['start_date'] = now()->subDays(30)->toDateString();
            $validated['end_date'] = now()->toDateString();
        }

        return $validated;
    }

    /**
     * Create DTO from validated request data.
     */
    public function createFilterAttendanceDTO(string $companyId): FilterAttendanceDTO
    {
        $validated = $this->validated();

        return new FilterAttendanceDTO(
            company_id: $companyId,
            user_id: $validated['user_id'] ?? null,
            status: $validated['status'] ?? null,
            start_date: $validated['start_date'] ?? null,
            end_date: $validated['end_date'] ?? null,
            late_arrival: $validated['is_late'] ?? null,
            early_departure: $validated['is_early_departure'] ?? null,
            // page: $validated['page'] ?? null,
            // per_page: $validated['per_page'] ?? null,
        );
    }
}
