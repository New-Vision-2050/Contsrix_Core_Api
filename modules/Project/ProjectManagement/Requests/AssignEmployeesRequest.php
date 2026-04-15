<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignEmployeesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_id' => 'required|string|exists:projects,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|string|exists:users,id',
            'project_role_id' => 'nullable|string|exists:project_roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Project ID is required',
            'project_id.exists' => 'Project does not exist',
            'user_ids.required' => 'User IDs array is required',
            'user_ids.array' => 'User IDs must be an array',
            'user_ids.min' => 'At least one user must be provided',
            'user_ids.*.required' => 'User ID is required',
            'user_ids.*.exists' => 'One or more users do not exist',
            'project_role_id.exists' => 'Project role does not exist',
        ];
    }
}
