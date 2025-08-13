<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\ValidateAttendanceDTO;

class ValidateAttendanceRequest extends FormRequest
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
            'clock_in_time' => 'required|date',
            'clock_in_location' => 'nullable|array',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string',
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createValidateAttendanceDTO(): ValidateAttendanceDTO
    {
        $validated = $this->validated();
        
        return new ValidateAttendanceDTO(
            userId: $validated['user_id'],
            clockInTime: $validated['clock_in_time'],
            clockInLocation: $validated['clock_in_location'] ?? null,
            ipAddress: $validated['ip_address'] ?? null,
            userAgent: $validated['user_agent'] ?? null
        );
    }
}
