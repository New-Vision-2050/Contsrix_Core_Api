<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Attendance\DTO\CreateLeaveRequestDTO;

class CreateLeaveRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'string', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_emergency' => ['sometimes', 'boolean'],
            'attachments' => ['sometimes', 'nullable', 'array'],
            'attachments.*' => ['string', 'max:255'],
            'contact_info' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'Leave type is required.',
            'leave_type_id.exists' => 'The selected leave type does not exist.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'attachments.array' => 'Attachments must be an array.',
            'attachments.*.string' => 'Each attachment must be a string.',
            'attachments.*.max' => 'Each attachment path cannot exceed 255 characters.',
            'contact_info.max' => 'Contact information cannot exceed 500 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set user_id and company_id from authenticated user
        if ($this->user()) {
            $this->merge([
                'user_id' => $this->user()->id,
                'company_id' => $this->user()->company_id ?? tenant('id'),
            ]);
        }

        // Convert boolean strings to actual booleans
        if ($this->has('is_emergency') && is_string($this->input('is_emergency'))) {
            $this->merge(['is_emergency' => filter_var($this->input('is_emergency'), FILTER_VALIDATE_BOOLEAN)]);
        }

        // Ensure attachments is an array if provided
        if ($this->has('attachments') && !is_array($this->input('attachments'))) {
            $attachments = $this->input('attachments');
            if (is_string($attachments)) {
                $this->merge(['attachments' => json_decode($attachments, true) ?: []]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->validated();

            // Check if start_date and end_date are valid
            if (isset($data['start_date']) && isset($data['end_date'])) {
                $startDate = \Carbon\Carbon::parse($data['start_date']);
                $endDate = \Carbon\Carbon::parse($data['end_date']);

                // Check if the date range is reasonable (not more than 1 year)
                if ($endDate->diffInDays($startDate) > 365) {
                    $validator->errors()->add('end_date', 'Leave duration cannot exceed 365 days.');
                }

                // Check if it's a weekend-only leave (might want to warn)
                if ($startDate->isWeekend() && $endDate->isWeekend() && $endDate->diffInDays($startDate) <= 2) {
                    // This is just a weekend, might want to add a warning
                }
            }

            // Validate emergency leave requirements
            if (isset($data['is_emergency']) && $data['is_emergency']) {
                if (empty($data['reason'])) {
                    $validator->errors()->add('reason', 'Reason is required for emergency leave requests.');
                }
                if (empty($data['contact_info'])) {
                    $validator->errors()->add('contact_info', 'Contact information is required for emergency leave requests.');
                }
            }
        });
    }

    /**
     * Create CreateLeaveRequestDTO from validated data
     */
    public function createLeaveRequestDTO(): CreateLeaveRequestDTO
    {
        $validated = $this->validated();
        
        return new CreateLeaveRequestDTO(
            user_id: $this->input('user_id'),
            company_id: $this->input('company_id'),
            leave_type_id: $validated['leave_type_id'],
            start_date: $validated['start_date'],
            end_date: $validated['end_date'],
            reason: $validated['reason'] ?? null,
            is_emergency: $validated['is_emergency'] ?? false,
            attachments: $validated['attachments'] ?? null,
            contact_info: $validated['contact_info'] ?? null,
        );
    }
}
