<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\ResolveViolationDTO;

class ResolveViolationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('resolve_attendance_violations');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'resolution_notes' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'resolution_notes.max' => 'Resolution notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Create DTO from validated request data.
     */
    public function createResolveViolationDTO(string $violationId, string $resolvedBy): ResolveViolationDTO
    {
        $validated = $this->validated();
        
        return new ResolveViolationDTO(
            violation_id: $violationId,
            resolved_by: $resolvedBy,
            resolution_notes: $validated['resolution_notes'] ?? null,
        );
    }
}
