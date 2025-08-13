<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\DismissViolationDTO;

class DismissViolationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('dismiss_attendance_violations');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'dismissal_reason' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'dismissal_reason.max' => 'Dismissal reason cannot exceed 1000 characters.',
        ];
    }

    /**
     * Create DTO from validated request data.
     */
    public function createDismissViolationDTO(string $violationId, string $dismissedBy): DismissViolationDTO
    {
        $validated = $this->validated();
        
        return new DismissViolationDTO(
            violation_id: $violationId,
            dismissed_by: $dismissedBy,
            dismissal_reason: $validated['dismissal_reason'] ?? null,
        );
    }
}
