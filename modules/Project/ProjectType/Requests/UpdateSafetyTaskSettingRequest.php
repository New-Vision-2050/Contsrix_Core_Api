<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Commands\UpdateSafetyTaskSettingCommand;
use Modules\Project\ProjectType\DTO\UpdateSafetyTaskSettingDTO;

class UpdateSafetyTaskSettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array { return ['is_shown' => ['sometimes', 'boolean']]; }

    public function toCommand(int $projectTypeId): UpdateSafetyTaskSettingCommand
    {
        return new UpdateSafetyTaskSettingCommand($projectTypeId, new UpdateSafetyTaskSettingDTO(
            is_shown: $this->has('is_shown') ? (bool) $this->input('is_shown') : null,
        ));
    }
}