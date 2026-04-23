<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\FilterAttendanceDTO; // Ensure this namespace is correct
use Illuminate\Support\Facades\Auth; // Needed for Auth::user()->company_id

class ExportAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Adjust authorization logic as needed, e.g., check for specific permissions
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'format' => ['sometimes', 'string', 'in:xlsx,csv'],

            'user_id' => ['sometimes', 'string', 'integer', 'exists:users,id'],
            'start_date' => ['sometimes', 'date', 'before_or_equal:end_date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', 'in:present,absent,late,on_leave,holiday,weekend'],
            'attendance_status' => ['sometimes', 'string', 'in:late,present,absent,holiday'],
            'department_id' => ['sometimes', 'string', 'integer', 'exists:departments,id'],
            'management_id' => ['sometimes', 'string', 'integer', 'exists:managements,id'],
            'branch_id' => ['sometimes', 'string', 'integer', 'exists:branches,id'],
            'constraint_id' => ['sometimes', 'string', 'integer', 'exists:constraints,id'],
            'user_search' => ['sometimes', 'string', 'max:255'],
            'user_name' => ['sometimes', 'string', 'max:255'],
            'user_email' => ['sometimes', 'string', 'email', 'max:255'],
            'work_hours_from' => ['sometimes', 'numeric', 'min:0'],
            'work_hours_to' => ['sometimes', 'numeric', 'min:0', 'gte:work_hours_from'],
            'break_duration_from' => ['sometimes', 'numeric', 'min:0'],
            'break_duration_to' => ['sometimes', 'numeric', 'min:0', 'gte:break_duration_from'],
            'overtime_hours_from' => ['sometimes', 'numeric', 'min:0'],
            'overtime_hours_to' => ['sometimes', 'numeric', 'min:0', 'gte:overtime_hours_from'],
            'location' => ['sometimes', 'string', 'max:255'],
            'ip_address' => ['sometimes', 'ip'],
            'is_late' => ['sometimes', 'boolean'],
            'early_departure' => ['sometimes', 'boolean'],
            'search_text' => ['sometimes', 'string', 'max:255'],

            'is_absent' => ['sometimes', 'boolean'],
            'is_holiday' => ['sometimes', 'boolean'],
            'day_status' => ['sometimes', 'string', 'in:working_day,weekend,holiday'],
        ];
    }

    /**
     * Create a FilterAttendanceDTO from the request.
     *
     * @param int $companyId The company ID from the authenticated user.
     * @return FilterAttendanceDTO
     */
    public function createFilterAttendanceDTO( $companyId): FilterAttendanceDTO
    {
        return new FilterAttendanceDTO(
            company_id: (string) $companyId,
            user_id: $this->has('user_id') ? (string) $this->input('user_id') : null,
            status: $this->input('status'),
            attendance_status: $this->input('attendance_status'),
            start_date: $this->input('start_date'),
            end_date: $this->input('end_date'),
            department_id: $this->input('department_id'),
            management_id: $this->input('management_id'),
            branch_id: $this->input('branch_id'),
            constraint_id: $this->input('constraint_id'),
            user_search: $this->input('user_search'),
            user_name: $this->input('user_name'),
            user_email: $this->input('user_email'),
            work_hours_from: $this->has('work_hours_from') ? (float)$this->input('work_hours_from') : null,
            work_hours_to: $this->has('work_hours_to') ? (float)$this->input('work_hours_to') : null,
            break_duration_from: $this->has('break_duration_from') ? (float)$this->input('break_duration_from') : null,
            break_duration_to: $this->has('break_duration_to') ? (float)$this->input('break_duration_to') : null,
            overtime_hours_from: $this->has('overtime_hours_from') ? (float)$this->input('overtime_hours_from') : null,
            overtime_hours_to: $this->has('overtime_hours_to') ? (float)$this->input('overtime_hours_to') : null,
            location: $this->input('location'),
            ip_address: $this->input('ip_address'),
            late_arrival: $this->boolean('is_late'),
            early_departure: $this->boolean('early_departure'),
            search_text: $this->input('search_text'),

        );
    }
}
