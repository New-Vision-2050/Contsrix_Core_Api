<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectSharingTasksSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id'               => ['sometimes', 'integer', 'exists:project_types,id'],
            'project_sharing_work_order_id' => ['sometimes', 'integer', 'exists:project_sharing_work_orders,id'],
            'project_sharing_task_id'       => ['sometimes', 'integer', 'exists:project_sharing_tasks,id'],
        ];
    }
}
