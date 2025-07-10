<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\RejectLeaveRequestDTO;
use Illuminate\Support\Facades\Auth;

class RejectLeaveRequestRequest extends FormRequest
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
            'reason' => 'nullable|string|max:500',
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createRejectLeaveRequestDTO(): RejectLeaveRequestDTO
    {
        $validated = $this->validated();
        
        return new RejectLeaveRequestDTO(
            rejecterId: Auth::id(),
            reason: $validated['reason'] ?? null
        );
    }
}
