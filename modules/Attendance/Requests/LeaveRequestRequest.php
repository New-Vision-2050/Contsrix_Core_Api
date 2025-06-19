<?php

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Models\LeaveType;

class LeaveRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_duration_type' => 'required|in:full_day,first_half,second_half',
            'reason' => 'nullable|string|max:500',
            'attachment_path' => 'nullable|string|max:255'
        ];
        
        // For new leave requests, start date should be today or in the future
        if ($this->isMethod('POST')) {
            $rules['start_date'] = 'required|date|after_or_equal:today';
            
            // Check if attachment is required based on leave type
            if ($this->filled('leave_type_id')) {
                $leaveType = LeaveType::find($this->input('leave_type_id'));
                if ($leaveType && $leaveType->requires_attachment) {
                    $rules['attachment_path'] = 'required|string|max:255';
                }
            }
        }
        
        return $rules;
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'leave_type_id.required' => 'Please select a leave type',
            'leave_type_id.exists' => 'The selected leave type is invalid',
            'start_date.required' => 'Start date is required',
            'start_date.date' => 'Start date must be a valid date',
            'start_date.after_or_equal' => 'Leave cannot start in the past',
            'end_date.required' => 'End date is required',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after_or_equal' => 'End date cannot be before the start date',
            'leave_duration_type.required' => 'Please specify if this is a full or half day leave',
            'leave_duration_type.in' => 'Leave duration type must be full day, first half, or second half',
            'attachment_path.required' => 'An attachment is required for this leave type',
        ];
    }
}
