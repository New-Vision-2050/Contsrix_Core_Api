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
            'employee_task_type_id'     => ['nullable', 'string', 'exists:employee_task_types,id'],
            'item_type'                 => ['nullable', 'string', 'max:255'  , 'exists:employee_task_items,key'],
            'item_id'                   => ['nullable', 'uuid'],
            'project_id'                => ['nullable', 'uuid','exists:projects,id'],
            'approval_responsible_id'   => ['nullable', 'uuid'],
            'assignment_responsible_id' => ['nullable', 'uuid'],
            'duration_hours'            => ['required', 'numeric', 'min:0.25', 'max:24'],
            'task_date'                 => ['required', 'date_format:Y-m-d'],
            'task_latitude'             => ['required', 'numeric', 'between:-90,90'],
            'task_longitude'            => ['required', 'numeric', 'between:-180,180'],
            'current_latitude'            => ['nullable', 'numeric', 'between:-90,90'],
            'current_longitude'           => ['nullable', 'numeric', 'between:-180,180'],
            'notes'                     => ['nullable', 'string'],
            'files'                     => ['nullable', 'array'],
            'files.*'                   => ['file', 'max:20480'],
        ];
    }
}
