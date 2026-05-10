<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectSharingTasksSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id'               => ['required', 'integer', 'exists:project_types,id'],
            'project_sharing_work_order_id' => ['required', 'integer', 'exists:project_sharing_work_orders,id'],
            'project_sharing_task_id'       => ['required', 'integer', 'exists:project_sharing_tasks,id'],
        ];
    }
}
