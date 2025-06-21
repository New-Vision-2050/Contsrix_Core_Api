<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\LeaveCalendarDTO;

class LeaveCalendarRequest extends FormRequest
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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'company_id' => 'nullable|uuid',
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createLeaveCalendarDTO(): LeaveCalendarDTO
    {
        $validated = $this->validated();
        
        return new LeaveCalendarDTO(
            startDate: $validated['start_date'] ?? now()->startOfMonth()->toDateString(),
            endDate: $validated['end_date'] ?? now()->endOfMonth()->toDateString(),
            companyId: $validated['company_id'] ?? null
        );
    }
}
