<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_id' => 'required|string|exists:projects,id',
            'company_serial_number' => 'required|string',
            'type_id' => 'nullable|integer|exists:project_share_types,id',
            'relation_id' => 'nullable|integer|exists:project_share_types,id',
            'role_id' => 'nullable|integer|exists:project_share_types,id',
            'schema_ids' => 'nullable|array',
            'schema_ids.*' => 'required|integer|exists:project_schemas,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Project ID is required',
            'project_id.exists' => 'Project does not exist',
            'company_serial_number.required' => 'Company serial number is required',
            'type_id.exists' => 'Selected type is invalid',
            'relation_id.exists' => 'Selected relation is invalid',
            'role_id.exists' => 'Selected role is invalid',
            'schema_ids.array' => 'Schema IDs must be an array',
            'schema_ids.*.exists' => 'One or more schema IDs are invalid',
        ];
    }
}
