<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\FilterStatisticsDTO;

class GetStatisticsRequest extends FormRequest
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
            'constraint_name' => ['sometimes', 'string'],
            'user_id' => ['sometimes', 'string', 'exists:users,id'],
            'department_id' => ['sometimes', 'string', 'exists:departments,id'],
            'start_date' => ['sometimes', 'date', 'before_or_equal:end_date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
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
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Create DTO from validated request data.
     */
    public function createFilterStatisticsDTO(string $companyId): FilterStatisticsDTO
    {
        $validated = $this->validated();
        
        return new FilterStatisticsDTO(
            company_id: $companyId,
            constraint_type: $validated['constraint_type'] ?? null,
            constraint_name: $validated['constraint_name'] ?? null,
            user_id: $validated['user_id'] ?? null,
            department_id: $validated['department_id'] ?? null,
            start_date: $validated['start_date'] ?? null,
            end_date: $validated['end_date'] ?? null,
        );
    }
}
