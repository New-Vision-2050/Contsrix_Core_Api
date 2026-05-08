<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\ClockOutDTO;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ClockOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'clock_out_time' => [
                'sometimes',
                'date',
                'before_or_equal:now'
            ],
            'location' => [
                'sometimes',
                'array'
            ],
            'location.latitude' => [
                'required_with:location',
                'numeric',
                'between:-90,90'
            ],
            'location.longitude' => [
                'required_with:location',
                'numeric',
                'between:-180,180'
            ],
            'location.address' => [
                'sometimes',
                'string',
                'max:255'
            ],
            'notes' => [
                'sometimes',
                'string',
                'max:1000'
            ],
            'ip_address' => [
                'sometimes',
                'ip'
            ],
            'user_agent' => [
                'sometimes',
                'string',
                'max:500'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'clock_out_time.before_or_equal' => 'Clock out time cannot be in the future.',
            'location.latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'location.longitude.between' => 'Longitude must be between -180 and 180 degrees.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default clock out time to now if not provided
        if (!$this->has('clock_out_time')) {
            $this->merge([
                'clock_out_time' => now()->toDateTimeString()
            ]);
        }

        // Add request metadata
        $this->merge([
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id ?? tenant('id')
        ]);
    }

    /**
     * Get validated data with additional context.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Ensure user and company IDs are included
        $validated['user_id'] = auth()->id();
        $validated['company_id'] = auth()->user()->company_id ?? tenant('id');

        return $validated;
    }

    /**
     * Create ClockOutDTO from validated request data.
     */
    public function createClockOutDTO(): ClockOutDTO
    {
        $validated = $this->validated();

        return new ClockOutDTO(
            user_id: Uuid::fromString((string)$validated['user_id']),
            company_id: Uuid::fromString((string) $validated['company_id']),
            clock_out_time: $validated['clock_out_time'],
            location: $validated['location'] ?? null,
            notes: $validated['notes'] ?? null,
            ip_address: $validated['ip_address'] ?? null,
            user_agent: $validated['user_agent'] ?? null,
        );
    }

    /** Standard DTO factory — delegates to createClockOutDTO(). */
    public function toDTO(): ClockOutDTO
    {
        return $this->createClockOutDTO();
    }
}
