<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\DTO\CreateProjectTypeDTO;

class CreateProjectTypeRequest extends FormRequest
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

    public function createCreateProjectTypeDTO(): CreateProjectTypeDTO
    {
        return new CreateProjectTypeDTO(
            name: $this->get('name'),
            icon: $this->get('icon'),
            parent_id: $this->get('parent_id'),
            is_have_schema: $this->boolean('is_have_schema', false),
            is_active: $this->boolean('is_active', true),
        );
    }
}
