<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\MyLeaveRequestsFilterDTO;
use Illuminate\Support\Facades\Auth;

class MyLeaveRequestsRequest extends FormRequest
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
            'status' => 'nullable|string|in:pending,approved,rejected,cancelled',
            'leave_type_id' => 'nullable|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createMyLeaveRequestsFilterDTO(): MyLeaveRequestsFilterDTO
    {
        $validated = $this->validated();
        
        return new MyLeaveRequestsFilterDTO(
            userId: Auth::id(),
            status: $validated['status'] ?? null,
            leaveTypeId: $validated['leave_type_id'] ?? null,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null
        );
    }
}
