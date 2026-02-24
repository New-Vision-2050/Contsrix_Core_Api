<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Project\TermSetting\DTO\CreateTermSettingDTO;

class CreateTermSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:term_settings,id',
            'project_type_id' => 'nullable|integer|exists:project_types,id',
            'term_services_ids' => 'nullable|array',
            'term_services_ids.*' => 'integer|exists:term_services,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function createCreateTermSettingDTO(): CreateTermSettingDTO
    {
        return new CreateTermSettingDTO(
            name: $this->get('name'),
            description: $this->get('description'),
            parentId: $this->get('parent_id'),
            projectTypeId: $this->get('project_type_id'),
            termServicesIds: $this->get('term_services_ids', []),
            isActive: $this->get('is_active', true),
        );
    }
}
