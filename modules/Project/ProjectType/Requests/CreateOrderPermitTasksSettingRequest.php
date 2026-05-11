<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderPermitTasksSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id'      => ['required', 'integer', 'exists:project_types,id'],
            'order_permit_id'      => ['required', 'integer', 'exists:order_permit,id'],
            'order_permit_task_id' => ['required', 'integer', 'exists:order_permit_tasks,id'],
        ];
    }
}
