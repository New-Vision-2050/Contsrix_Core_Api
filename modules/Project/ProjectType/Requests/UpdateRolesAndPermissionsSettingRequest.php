<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateRolesAndPermissionsSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateRolesAndPermissionsSettingDTO;

class UpdateRolesAndPermissionsSettingRequest extends FormRequest
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

    public function toCommand(int $projectTypeId): UpdateRolesAndPermissionsSettingCommand
    {
        $dto = new UpdateRolesAndPermissionsSettingDTO(
            is_all_data_visible: $this->has('is_all_data_visible') ? (int) $this->input('is_all_data_visible') : null,
        );

        return new UpdateRolesAndPermissionsSettingCommand($projectTypeId, $dto);
    }
}
