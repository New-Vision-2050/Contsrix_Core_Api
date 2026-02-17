<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateProjectTypeCommand;

class UpdateProjectTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer|exists:project_types,id',
            'is_have_schema' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function createUpdateProjectTypeCommand(): UpdateProjectTypeCommand
    {
        return new UpdateProjectTypeCommand(
            id: (int) $this->route('id'),
            name: $this->get('name'),
            icon: $this->get('icon'),
            parent_id: $this->get('parent_id'),
            is_have_schema: $this->has('is_have_schema') ? $this->boolean('is_have_schema') : null,
            is_active: $this->has('is_active') ? $this->boolean('is_active') : null,
        );
    }
}
