<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\LeaveConflictCheckDTO;

class LeaveConflictCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|uuid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'exclude_request_id' => 'nullable|uuid',
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createLeaveConflictCheckDTO(): LeaveConflictCheckDTO
    {
        $validated = $this->validated();
        
        return new LeaveConflictCheckDTO(
            userId: $validated['user_id'],
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            excludeRequestId: $validated['exclude_request_id'] ?? null
        );
    }
}
