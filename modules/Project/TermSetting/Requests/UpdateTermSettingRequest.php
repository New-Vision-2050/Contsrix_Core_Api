<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Project\TermSetting\Commands\UpdateTermSettingCommand;
use Modules\Project\TermSetting\Handlers\UpdateTermSettingHandler;

class UpdateTermSettingRequest extends FormRequest
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

    public function createUpdateTermSettingCommand(): UpdateTermSettingCommand
    {
        return new UpdateTermSettingCommand(
            id: (int) $this->route('id'),
            name: $this->get('name'),
            description: $this->get('description'),
            parentId: $this->get('parent_id'),
            projectTypeId: $this->get('project_type_id'),
            termServicesIds: $this->get('term_services_ids', []),
            isActive: $this->get('is_active'),
        );
    }
}
