<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateSecondLevelProjectTypeCommand;

class UpdateSecondLevelProjectTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('id');
        
        return [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'required|integer|exists:project_types,id',
            'reference_project_type_id' => 'nullable|integer|exists:project_types,id',
            'schema_ids' => 'required|array|min:1',
            'schema_ids.*' => 'required|integer|exists:project_schemas,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.required' => 'Parent project type is required',
            'parent_id.exists' => 'Parent project type does not exist',
            'reference_project_type_id.exists' => 'Reference project type does not exist',
            'schema_ids.required' => 'At least one schema is required',
            'schema_ids.*.exists' => 'One or more schemas do not exist',
        ];
    }

    public function createCommand(): UpdateSecondLevelProjectTypeCommand
    {
        return new UpdateSecondLevelProjectTypeCommand(
            id: (int) $this->route('id'),
            name: $this->get('name'),
            icon: $this->get('icon'),
            parent_id: $this->get('parent_id'),
            reference_project_type_id: $this->get('reference_project_type_id'),
            schema_ids: $this->get('schema_ids', []),
            is_active: $this->boolean('is_active', true),
        );
    }
}
