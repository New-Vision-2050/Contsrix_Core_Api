<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateProjectSharingSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateProjectSharingSettingDTO;

class UpdateProjectSharingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function toCommand(int $projectTypeId): UpdateProjectSharingSettingCommand
    {
        $dto = new UpdateProjectSharingSettingDTO(
            is_enabled: $this->has('is_enabled') ? (int) $this->input('is_enabled') : null,
        );

        return new UpdateProjectSharingSettingCommand($projectTypeId, $dto);
    }
}
