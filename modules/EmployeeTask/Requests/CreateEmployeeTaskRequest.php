<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                     => ['required', 'string', 'max:255'],
            'description'               => ['nullable', 'string'],
            'project_id'                => ['nullable', 'uuid'],
            'approval_responsible_id'   => ['nullable', 'uuid'],
            'assignment_responsible_id' => ['nullable', 'uuid'],
            'internal_process_type_id'  => ['nullable', 'uuid', 'exists:internal_process_types,id'],
            'duration_hours'            => ['required', 'numeric', 'min:0.25', 'max:24'],
            'task_date'                 => ['required', 'date_format:Y-m-d'],
            'task_latitude'             => ['required', 'numeric', 'between:-90,90'],
            'task_longitude'            => ['required', 'numeric', 'between:-180,180'],
            'notes'                     => ['nullable', 'string'],
        ];
    }
}
