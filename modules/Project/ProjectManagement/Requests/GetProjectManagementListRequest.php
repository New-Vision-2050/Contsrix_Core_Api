<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetProjectManagementListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
            'name' => 'nullable|string',
            'project_type_id' => 'nullable|integer',
            'sub_project_type_id' => 'nullable|integer',
            'sub_sub_project_type_id' => 'nullable|integer',
            'responsible_employee_id' => 'nullable|uuid',
            'client_id' => 'nullable|uuid',
            'management_id' => 'nullable|uuid',
            'status' => 'nullable|integer|in:-1,0,1',
        ];
    }
}
