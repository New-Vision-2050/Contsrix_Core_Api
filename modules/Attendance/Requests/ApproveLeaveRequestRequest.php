<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\ApproveLeaveRequestDTO;
use Illuminate\Support\Facades\Auth;

class ApproveLeaveRequestRequest extends FormRequest
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
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createApproveLeaveRequestDTO(): ApproveLeaveRequestDTO
    {
        $validated = $this->validated();
        
        return new ApproveLeaveRequestDTO(
            approverId: Auth::id(),
            notes: $validated['notes'] ?? null
        );
    }
}
