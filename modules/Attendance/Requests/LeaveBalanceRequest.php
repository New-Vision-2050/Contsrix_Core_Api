<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\LeaveBalanceDTO;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceRequest extends FormRequest
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
            'user_id' => 'nullable|uuid',
            'leave_type_id' => 'nullable|uuid',
            'year' => 'nullable|integer|min:2000|max:2100',
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createLeaveBalanceDTO(): LeaveBalanceDTO
    {
        $validated = $this->validated();
        
        return new LeaveBalanceDTO(
            userId: $validated['user_id'] ?? Auth::id(),
            leaveTypeId: $validated['leave_type_id'] ?? null,
            year: (int)($validated['year'] ?? now()->year)
        );
    }
}
