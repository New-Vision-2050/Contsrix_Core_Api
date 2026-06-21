<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\User\Models\User;

class AttendanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();
        if ($user === null || ! $user->can(Permission::ATTENDANCE_REPORTS_VIEW())) {
            return false;
        }

        $employeeId = $this->input('employee_id');
        if ($employeeId === null || $employeeId === '') {
            return true;
        }

        return User::query()
            ->where('company_id', $user->company_id)
            ->where('id', $employeeId)
            ->exists();
    }

    public function rules(): array
    {
        $companyId = (string) Auth::user()?->company_id;

        return [
            'employee_id' => [
                'required',
                'string',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'year' => ['nullable', 'required_with:month', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDTO(): AttendanceReportFilterDTO
    {
        $validated = $this->validated();

        return new AttendanceReportFilterDTO(
            company_id: (string) Auth::user()->company_id,
            employee_id: $validated['employee_id'],
            from_date: $validated['from_date'] ?? null,
            to_date: $validated['to_date'] ?? null,
            year: isset($validated['year']) ? (int) $validated['year'] : null,
            month: isset($validated['month']) ? (int) $validated['month'] : null,
            page: isset($validated['page']) ? (int) $validated['page'] : 1,
            per_page: isset($validated['per_page']) ? (int) $validated['per_page'] : 12,
        );
    }
}
