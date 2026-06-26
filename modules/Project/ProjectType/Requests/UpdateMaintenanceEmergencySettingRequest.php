<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateMaintenanceEmergencySettingCommand;
use Modules\Project\ProjectType\DTO\UpdateMaintenanceEmergencySettingDTO;

class UpdateMaintenanceEmergencySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_shown' => ['sometimes', 'boolean'],
        ];
    }

    public function toCommand(int $projectTypeId): UpdateMaintenanceEmergencySettingCommand
    {
        $dto = new UpdateMaintenanceEmergencySettingDTO(
            is_shown: $this->has('is_shown') ? (int) $this->input('is_shown') : null,
        );

        return new UpdateMaintenanceEmergencySettingCommand($projectTypeId, $dto);
    }
}
