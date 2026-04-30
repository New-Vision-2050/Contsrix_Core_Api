<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\FilterAttendanceDTO;

class FilterAttendanceRequest extends FormRequest
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
            'user_id' => ['sometimes', 'string', 'exists:users,id'],
            'status' => ['sometimes', 'string', 'in:active,completed,pending_approval,approved,rejected'],
            'attendance_status' => ['sometimes', 'string', 'in:late,present,absent,holiday'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'department_id' => ['sometimes', 'string', 'exists:management_hierarchies,id'],
            'management_id' => ['sometimes', 'string', 'exists:management_hierarchies,id'],
            'branch_id' => ['sometimes', 'string', 'exists:management_hierarchies,id'],
            'constraint_id' => ['sometimes', 'string', 'exists:attendance_constraints,id'],
            'user_search' => ['sometimes', 'string'],
            'user_name' => ['sometimes', 'string'],
            'user_email' => ['sometimes', 'string', 'email'],
            'work_hours_from' => ['sometimes', 'numeric', 'min:0', 'lte:work_hours_to'],
            'work_hours_to' => ['sometimes', 'numeric', 'min:0', 'gte:work_hours_from'],
            'break_duration_from' => ['sometimes', 'numeric', 'min:0', 'lte:break_duration_to'],
            'break_duration_to' => ['sometimes', 'numeric', 'min:0', 'gte:break_duration_from'],
            'overtime_hours_from' => ['sometimes', 'numeric', 'min:0', 'lte:overtime_hours_to'],
            'overtime_hours_to' => ['sometimes', 'numeric', 'min:0', 'gte:overtime_hours_from'],
            'location' => ['sometimes', 'string'],
            'ip_address' => ['sometimes', 'string'],
            'late_arrival' => ['sometimes', 'boolean'],
            'early_departure' => ['sometimes', 'boolean'],
            'search_text'=> ['sometimes', 'string'],
            'employee_status' => ['sometimes', 'integer'],
            // 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            // 'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'The selected user does not exist.',
            'department_id.exists' => 'The selected department does not exist.',
            'status.in' => 'The status must be one of: active, completed, pending_approval, approved, rejected.',
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'user_email.email' => 'The user email must be a valid email address.',
            'work_hours_from.lte' => 'The minimum work hours must be less than or equal to the maximum work hours.',
            'work_hours_to.gte' => 'The maximum work hours must be greater than or equal to the minimum work hours.',
            'break_duration_from.lte' => 'The minimum break duration must be less than or equal to the maximum break duration.',
            'break_duration_to.gte' => 'The maximum break duration must be greater than or equal to the minimum break duration.',
            'overtime_hours_from.lte' => 'The minimum overtime hours must be less than or equal to the maximum overtime hours.',
            'overtime_hours_to.gte' => 'The maximum overtime hours must be greater than or equal to the minimum overtime hours.',
            'per_page.max' => 'The per page value cannot exceed 100.',
        ];
    }

    /**
     * Create DTO from validated request data.
     */
    public function createFilterAttendanceDTO(string $companyId): FilterAttendanceDTO
    {
        $validated = $this->validated();

        if (!isset($validated['end_date'])) {
            $validated['end_date'] = now()->toDateString();
        }

        return new FilterAttendanceDTO(
            company_id: $companyId,
            user_id: $validated['user_id'] ?? null,
            status: $validated['status'] ?? null,
            attendance_status: $validated['attendance_status'] ?? null,
            start_date: $validated['start_date'] ?? null,
            end_date: $validated['end_date'] ?? null,
            department_id: $validated['department_id'] ?? null,
            management_id: $validated['management_id'] ?? null,
            branch_id: $validated['branch_id'] ?? null,
            constraint_id: $validated['constraint_id'] ?? null,
            user_search: $validated['user_search'] ?? null,
            user_name: $validated['user_name'] ?? null,
            user_email: $validated['user_email'] ?? null,
            work_hours_from: $validated['work_hours_from'] ?? null,
            work_hours_to: $validated['work_hours_to'] ?? null,
            break_duration_from: $validated['break_duration_from'] ?? null,
            break_duration_to: $validated['break_duration_to'] ?? null,
            overtime_hours_from: $validated['overtime_hours_from'] ?? null,
            overtime_hours_to: $validated['overtime_hours_to'] ?? null,
            location: $validated['location'] ?? null,
            ip_address: $validated['ip_address'] ?? null,
            late_arrival: $validated['late_arrival'] ?? null,
            early_departure: $validated['early_departure'] ?? null,
            search_text: $validated['search_text'] ?? null,
            employee_status: $validated['employee_status'] ?? null,
            // page: $validated['page'] ?? null,
            // per_page: $validated['per_page'] ?? null,
        );
    }
}
