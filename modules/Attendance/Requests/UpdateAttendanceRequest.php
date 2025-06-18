<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'clock_in_time' => ['sometimes', 'date', 'before:clock_out_time'],
            'clock_out_time' => ['sometimes', 'date', 'after:clock_in_time'],
            'break_start_time' => ['sometimes', 'nullable', 'date', 'after:clock_in_time', 'before:break_end_time'],
            'break_end_time' => ['sometimes', 'nullable', 'date', 'after:break_start_time', 'before:clock_out_time'],
            'clock_in_location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'clock_out_location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'completed', 'pending_approval', 'approved', 'rejected'])],
            'is_late' => ['sometimes', 'boolean'],
            'is_early_departure' => ['sometimes', 'boolean'],
            'late_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'early_departure_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'clock_in_time.before' => 'Clock in time must be before clock out time.',
            'clock_out_time.after' => 'Clock out time must be after clock in time.',
            'break_start_time.after' => 'Break start time must be after clock in time.',
            'break_start_time.before' => 'Break start time must be before break end time.',
            'break_end_time.after' => 'Break end time must be after break start time.',
            'break_end_time.before' => 'Break end time must be before clock out time.',
            'clock_in_location.max' => 'Clock in location cannot exceed 500 characters.',
            'clock_out_location.max' => 'Clock out location cannot exceed 500 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'status.in' => 'Status must be one of: active, completed, pending_approval, approved, rejected.',
            'late_minutes.min' => 'Late minutes cannot be negative.',
            'early_departure_minutes.min' => 'Early departure minutes cannot be negative.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        if ($this->has('is_late') && is_string($this->input('is_late'))) {
            $this->merge(['is_late' => filter_var($this->input('is_late'), FILTER_VALIDATE_BOOLEAN)]);
        }

        if ($this->has('is_early_departure') && is_string($this->input('is_early_departure'))) {
            $this->merge(['is_early_departure' => filter_var($this->input('is_early_departure'), FILTER_VALIDATE_BOOLEAN)]);
        }

        // Convert numeric strings to integers
        if ($this->has('late_minutes') && is_string($this->input('late_minutes'))) {
            $this->merge(['late_minutes' => (int) $this->input('late_minutes')]);
        }

        if ($this->has('early_departure_minutes') && is_string($this->input('early_departure_minutes'))) {
            $this->merge(['early_departure_minutes' => (int) $this->input('early_departure_minutes')]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic
            $data = $this->validated();

            // If break times are provided, ensure they are logical
            if (isset($data['break_start_time']) && isset($data['break_end_time'])) {
                $breakStart = \Carbon\Carbon::parse($data['break_start_time']);
                $breakEnd = \Carbon\Carbon::parse($data['break_end_time']);
                
                if ($breakEnd->lte($breakStart)) {
                    $validator->errors()->add('break_end_time', 'Break end time must be after break start time.');
                }
            }

            // Validate that clock times are within reasonable bounds (not too far in the past or future)
            if (isset($data['clock_in_time'])) {
                $clockIn = \Carbon\Carbon::parse($data['clock_in_time']);
                if ($clockIn->isFuture()) {
                    $validator->errors()->add('clock_in_time', 'Clock in time cannot be in the future.');
                }
                if ($clockIn->lt(now()->subDays(7))) {
                    $validator->errors()->add('clock_in_time', 'Clock in time cannot be more than 7 days in the past.');
                }
            }

            if (isset($data['clock_out_time'])) {
                $clockOut = \Carbon\Carbon::parse($data['clock_out_time']);
                if ($clockOut->isFuture()) {
                    $validator->errors()->add('clock_out_time', 'Clock out time cannot be in the future.');
                }
            }
        });
    }
}
