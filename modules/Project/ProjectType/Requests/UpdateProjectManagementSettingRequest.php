<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateProjectManagementSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateProjectManagementSettingDTO;

class UpdateProjectManagementSettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array { return ['is_shown' => ['sometimes', 'boolean']]; }

    public function toCommand(int $projectTypeId): UpdateProjectManagementSettingCommand
    {
        return new UpdateProjectManagementSettingCommand($projectTypeId, new UpdateProjectManagementSettingDTO(
            is_shown: $this->has('is_shown') ? (bool) $this->input('is_shown') : null,
        ));
    }
}