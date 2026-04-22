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
            'is_all_data_visible' => ['sometimes', 'boolean'],
        ];
    }

    public function toCommand(int $projectTypeId): UpdateProjectSharingSettingCommand
    {
        $dto = new UpdateProjectSharingSettingDTO(
            is_all_data_visible: $this->has('is_all_data_visible') ? (int) $this->input('is_all_data_visible') : null,
        );

        return new UpdateProjectSharingSettingCommand($projectTypeId, $dto);
    }
}
